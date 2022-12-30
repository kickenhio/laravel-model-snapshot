<?php namespace Kickenhio\LaravelSqlSnapshot\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;
use Kickenhio\LaravelSqlSnapshot\Facades\Snapshot as SnapshotSQL;
use Kickenhio\LaravelSqlSnapshot\Structure\EntrypointModel;

/**
 *
 */
class SnapshotApplicationController extends BaseController
{
    /**
     * @param Request $request
     * @param string $manifest
     *
     * @return JsonResponse
     */
    public function snapshot(Request $request, string $manifest): JsonResponse
    {
        set_time_limit(0);

        try {
            $snapshot = SnapshotSQL::fromManifest($manifest);
        } catch (InvalidManifestSyntaxException $e) {
            return new JsonResponse([ 'message' => $e->getMessage() ], 501);
        }

        $output = collect();
        $iteration = 0;

        if (empty($config = config("snapshot.manifests.$manifest"))) {
            return new JsonResponse([ 'message' => 'Missing config' ], 501);
        }

        try {
            foreach ($request->input('identifiers', []) as $id) {
                $result = $snapshot->retrieveEntrypoint($request->input('model'), $request->input('entrypoint', 'ID'), $id);

                if ($result->count() > 1) {
                    return new JsonResponse([
                        'message' => 'Multiple',
                        'proposals' => $result->proposals()
                    ], 409);
                }

                $result->toSql()->each(function (string $query) use ($output, &$iteration) {
                    $output->push([
                        'i'     => ++$iteration,
                        'query' => $query,
                    ]);
                });
            }

            $key = $config['encryption']['key'];
            $IV = $config['encryption']['iv'];
            $frame = isset($config['encryption']['frame']) ? intval($config['encryption']['frame']) : 12;

            $cryptoString = openssl_encrypt($output->toJson(), "aes-128-cbc", $key, 16, $IV);

            $random = function ($length) : string {
                $string = '';

                while (($len = strlen($string)) < $length) {
                    $size = $length - $len;
                    $string .= substr(str_replace(['='], '', base64_encode(random_bytes($size))), 0, $size);
                }

                return $string;
            };

            $payload = '';
            foreach (str_split($cryptoString, $frame * 4) as $part) {
                $payload .= $random($frame) . $part . $random($frame);
            }

            $response = [
                'image_time' => time(),
                'snapshot' => $payload,
            ];

            return new JsonResponse($response, 200);
        } catch (InvalidManifestSyntaxException $ex) {
            return new JsonResponse([ 'message' => $ex->getMessage() ], 501);
        }
    }

    /**
     * @param string $manifest
     *
     * @return JsonResponse
     */
    public function entrypoints(string $manifest): JsonResponse
    {
        $snapshot = SnapshotSQL::fromManifest($manifest);

        return new JsonResponse(
            collect($snapshot->getManifest()->entrypointModels())->mapWithKeys(function (EntrypointModel $model) {
                return [ $model->getName() => array_keys($model->getEntryPoints()) ];
            })
        );
    }

    /**
     * @return JsonResponse
     */
    public function manifests(): JsonResponse
    {
        return new JsonResponse(array_keys(config('snapshot.manifests', [])));
    }
}