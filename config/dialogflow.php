<?php

return [
    'project_id' => env('DIALOGFLOW_PROJECT_ID'),
    'language' => env('DIALOGFLOW_LANGUAGE', 'en'),
    'session_id' => env('DIALOGFLOW_SESSION_ID', 'default'),
    'credentials_path' => env('DIALOGFLOW_CREDENTIALS_PATH'),
    'region' => env('DIALOGFLOW_REGION'),
];
