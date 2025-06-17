<?php

namespace App\Http\Controllers;
use App\Models\SysNodo;
use Illuminate\Http\Request;
class NodoController extends Controller
{
    public function informarConexion(Request $request)
    {
        $retorno = [
            'estado' => '0',
            'registro' => null,
        ];

        if ($request->isMethod('post')) {
            $rawContent = $request->getContent();
            var_dump('Contenido crudo recibido:');
            var_dump($rawContent);
            echo "<hr>";

            $params = json_decode($rawContent, true);
            var_dump('JSON decodificado:');
            var_dump($params);
            echo "<hr>";

            if (isset($params['idNodo'])) {
                $nodo = SysNodo::find($params['idNodo']);
                var_dump('Resultado de SysNodo::find():');
                var_dump($nodo);
                echo "<hr>";

                if ($nodo) {
                    $nodo->flgConexion = $params['estado'] ?? '1';
                    $nodo->fechaConexion = now();
                    $nodo->save();

                    $retorno['estado'] = '1';
                    $retorno['registro'] = $nodo->idNodo;

                    var_dump('Nodo actualizado:');
                    var_dump($retorno);
                    echo "<hr>";

                    return response()->json($retorno);
                } else {
                    var_dump("Nodo no encontrado con idNodo: " . $params['idNodo']);
                    exit();
                }
            } else {
                var_dump('idNodo no presente en el JSON recibido');
                exit();
            }
        }

        var_dump('No se recibió POST');
        exit();
    }
}
