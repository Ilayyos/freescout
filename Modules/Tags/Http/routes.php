<?php

Route::group([
    'middleware' => ['web', 'auth'],
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\\Tags\\Http\\Controllers',
], function () {
    Route::get('tags', 'TagsController@index')->name('tags');
    Route::post('tags', 'TagsController@store')->name('tags.store');
    Route::put('tags/{tag}', 'TagsController@update')->name('tags.update');
    Route::delete('tags/{tag}', 'TagsController@destroy')->name('tags.destroy');

    Route::get('tags/suggest', 'ConversationTagsController@suggest')->name('tags.suggest');
    Route::put('conversations/{conversation}/tags', 'ConversationTagsController@sync')->name('tags.conversations.sync');
});
