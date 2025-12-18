<?php

use Illuminate\Support\Facades\Route;
use OpenAI\Laravel\Facades\OpenAI;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/openai-test', function () {
    $response = OpenAI::responses()->create([
        'model' => 'gpt-5',
        'input' => 'Hello!',
    ]);

    // return $response->outputText;
    echo $response->outputText;
});
