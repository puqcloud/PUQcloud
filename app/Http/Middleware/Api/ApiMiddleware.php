<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
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

class ApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {

        $response = $next($request);

        if ($response->headers->get('Content-Type') === 'application/pdf') {
            return $response;
        }

        $statusCode = $response->getStatusCode();
        $data = json_decode($response->content(), true);
        $answer = [];

        $answer['message'] = null;
        $answer['errors'] = null;
        $answer['data'] = null;

        if (! empty($data['message'])) {
            $answer['message'] = $data['message'];
        }

        if (! empty($data['errors'])) {
            $answer['errors'] = $data['errors'];
        }

        if (! empty($data['data'])) {
            $answer['data'] = $data['data'];
        }

        if (! empty($data['redirect'])) {
            $answer['redirect'] = $data['redirect'];
        }

        $errorCodes = [
            400 => __('error.Bad request'),
            401 => __('error.Unauthorized'),
            403 => __('error.Forbidden'),
            404 => __('error.Not found'),
            409 => __('error.Conflict'),
            422 => __('error.Unprocessable entity'),
            500 => __('error.Internal server error'),
        ];

        if ($statusCode === 200) {
            $answer['status'] = 'success';
        } else {
            $answer['status'] = 'error';
            if (array_key_exists($statusCode, $errorCodes)) {
                $answer['errors'][] = $errorCodes[$statusCode];
            } else {
                $answer['errors'][] = __('error.Unexpected internal error');
            }
        }
        $response->setContent(json_encode($answer));

        return $response;
    }
}
