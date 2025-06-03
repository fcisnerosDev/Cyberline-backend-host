<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

trait ServiceFacturacionTrait
{
    protected function consumirServicioFacturacion(string $endpoint, array $data = [], string $method = 'GET')
    {
        $baseUrl = env('SERVICE_FACTURACION');

        if (!$baseUrl) {
            return [
                'error' => true,
                'message' => 'La variable SERVICE_FACTURACION no está configurada en el .env'
            ];
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->{$method}("$baseUrl/$endpoint", $data);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'error' => true,
                'status' => $response->status(),
                'message' => $response->body()
            ];
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Error en la conexión con el servicio de facturación.',
                'exception' => $e->getMessage()
            ];
        }
    }
}
