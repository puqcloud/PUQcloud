<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        if ($response->headers->get('Content-Type') === 'application/pdf') {
            return $response;
        }

        $response->headers->set('Content-Type', 'application/json');

        $statusCode = $response->getStatusCode();
        $data = json_decode($response->content(), true) ?? [];

        if (isset($data['raw'])) {
            $response->setContent(json_encode($data['raw']));

            return $response;
        }

        $answer = [];

        $answer['status'] = $statusCode === 200 ? 'success' : 'error';

        if (! empty($data['message'])) {
            $answer['message'] = $data['message'];
        }

        if (! empty($data['errors'])) {
            $answer['errors'] = array_values(array_unique($data['errors']));
        }

        if (! empty($data['data'])) {
            $answer['data'] = $data['data'];
        }

        if (! empty($data['redirect'])) {
            $answer['redirect'] = $data['redirect'];
        }

        if ($statusCode !== 200) {
            $errorCodes = [
                400 => __('error.Bad request'),
                401 => __('error.Unauthorized'),
                403 => __('error.Forbidden'),
                404 => __('error.Not found'),
                409 => __('error.Conflict'),
                422 => __('error.Unprocessable entity'),
                500 => __('error.Internal server error'),
            ];

            $systemError = $errorCodes[$statusCode] ?? __('error.Unexpected internal error');
            $answer['errors'][] = $systemError;
            $answer['errors'] = array_values(array_unique($answer['errors']));
        }

        $response->setContent(json_encode($answer));

        return $response;
    }
}
