<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('snapshot.api_prefix'), 'namespace' => 'Kickenhio\LaravelSqlSnapshot\Controllers'], function() {
    Route::get('manifests', ['uses' => 'SnapshotApplicationController@manifests']);
    Route::get('{manifest}/entrypoints', ['uses' => 'SnapshotApplicationController@entrypoints']);
    Route::post('{manifest}/snapshot', ['uses' => 'SnapshotApplicationController@snapshot']);
});