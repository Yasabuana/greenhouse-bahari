<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSensorReadingRequest extends FormRequest
{
    /**
     * Endpoint ingestion bersifat terbuka untuk bridge LoRa lokal.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'gh' => ['required', 'string', 'in:GH1,GH2'],
            'node' => ['required', 'string', 'in:N1,N2,N3'],

            // Sensor NPK 8-in-1 (media tanam)
            'n' => ['required', 'numeric', 'min:0', 'max:1000'],
            'p' => ['required', 'numeric', 'min:0', 'max:1000'],
            'k' => ['required', 'numeric', 'min:0', 'max:1000'],
            'soil_moisture' => ['required', 'numeric', 'between:0,100'],
            'soil_temp' => ['required', 'numeric', 'between:-10,70'],
            'ph' => ['required', 'numeric', 'between:0,14'],
            'ec' => ['required', 'numeric', 'min:0', 'max:50000'],
            'salinity' => ['required', 'numeric', 'min:0', 'max:50000'],

            // Sensor SHT31 (udara)
            'air_temp' => ['required', 'numeric', 'between:-20,80'],
            'air_humidity' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    /**
     * Pastikan node benar-benar terdaftar pada greenhouse yang dikirim.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $greenhouseId = (string) $this->input('gh');
                $nodeId = (string) $this->input('node');
                $allowedNodes = config("greenhouse.greenhouses.{$greenhouseId}.nodes", []);

                if (! in_array($nodeId, $allowedNodes, true)) {
                    $validator->errors()->add(
                        'node',
                        "Node {$nodeId} tidak terdaftar pada greenhouse {$greenhouseId}."
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gh.required' => 'Greenhouse wajib diisi (GH1/GH2).',
            'gh.in' => 'Greenhouse harus GH1 atau GH2.',
            'node.required' => 'Node wajib diisi (N1/N2/N3).',
            'node.in' => 'Node harus N1, N2, atau N3.',
        ];
    }
}
