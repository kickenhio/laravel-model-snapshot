<?php namespace Kickenhio\LaravelSqlSnapshot\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Routing\Controller as BaseController;
use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;
use Kickenhio\LaravelSqlSnapshot\Facades\Snapshot as SnapshotSQL;
use Kickenhio\LaravelSqlSnapshot\Query\ModelRetriever;

class SnapshotApplicationController extends BaseController
{
    /**
     * @param Request $request
     * @param string $manifest
     *
     * @return JsonResponse
     * @throws \Exception
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

        try {
            foreach ($request->input('identifiers', []) as $id) {
                $result = $snapshot->retrieveEntrypoint($request->input('model'), $request->input('entrypoint', 'ID'), $id);

                if ($result->count() > 1) {
                    return new JsonResponse([
                        'message' => 'Multiple'
                    ], 409);
                }

                $result->each(function (ModelRetriever $retriever) use ($output, &$iteration) {
                    $output->push([
                        'i'     => ++$iteration,
                        'query' => $retriever->toSql(),
                    ]);
                });
            }

            $key = "Pqan5NghSZ4vZBuP";
            $iv = "JabXMkEh92EVxUnq";

            $cryptoString = openssl_encrypt($output->toJson(), "aes-128-cbc", $key, 16, $iv);

            $random = function ($length) : string {
                $string = '';

                while (($len = strlen($string)) < $length) {
                    $size = $length - $len;
                    $string .= substr(str_replace(['='], '', base64_encode(random_bytes($size))), 0, $size);
                }

                return $string;
            };

            $payload = '';
            foreach (str_split($cryptoString, 40) as $part) {
                $payload .= $random(12) . $part . $random(12);
            }

            $response = [
                'image_time' => Carbon::now()->toDateTimeString(),
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
    public function available(string $manifest): JsonResponse
    {
        $manifest = new ManifestBuilder(sprintf('%s.json', $manifest));

        return new JsonResponse(
            $manifest->getManifest()->getModels()->keys()
        );
    }

    /**
     * @param string $manifest
     *
     * @return JsonResponse
     */
    public function entrypoints(string $manifest): JsonResponse
    {
        $manifest = new ManifestBuilder(sprintf('%s.json', $manifest));

        return new JsonResponse(
            $manifest->getManifest()->getModels()->mapWithKeys(function (ManifestModel $model) {
                return [ $model->getName() => $model->getEntryPoints()->keys() ];
            })
        );
    }

    /**
     * @return JsonResponse
     */
    public function manifests(): JsonResponse
    {
        $manifests = array_keys(config('snapshot.manifests', []));

        return new JsonResponse($manifests);
    }
}