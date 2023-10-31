<?php
use Ramsey\Uuid\Uuid;

function json_response($response, $data, $status = 200) {
	$response = $response->withHeader('Content-Type', 'application/json');
    $response->withStatus($status)->getBody()->write(
        json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    );

    return $response;
}

function uuid() {
	return Uuid::uuid4()->toString();
}