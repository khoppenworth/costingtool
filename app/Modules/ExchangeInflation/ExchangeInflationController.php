<?php
declare(strict_types=1);

namespace App\Modules\ExchangeInflation;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Validation\Validator;

class ExchangeInflationController extends Controller
{
    public function edit(): Response
    {
        $assessmentId = (int) $this->params['id'];
        $rows = $this->db()->all('SELECT * FROM exchange_inflation_rates WHERE assessment_id = :id ORDER BY year', ['id' => $assessmentId]);
        return $this->render('exchange_inflation.edit', compact('assessmentId', 'rows'));
    }

    public function update(): Response
    {
        $assessmentId = (int) $this->params['id'];
        $years = $this->request->post['year'] ?? [];
        $etbUsd = $this->request->post['etb_per_usd'] ?? [];
        $interest = $this->request->post['interest_rate'] ?? [];
        $inflation = $this->request->post['inflation_rate'] ?? [];
        $notes = $this->request->post['source_notes'] ?? [];

        $this->db()->statement('DELETE FROM exchange_inflation_rates WHERE assessment_id = :id', ['id' => $assessmentId]);

        foreach ($years as $i => $year) {
            $row = [
                'year' => $year,
                'etb_per_usd' => $etbUsd[$i] ?? null,
                'interest_rate' => $interest[$i] ?? null,
                'inflation_rate' => $inflation[$i] ?? null,
            ];

            $validator = new Validator($row, [
                'year' => ['required', 'numeric', 'min:2000'],
                'etb_per_usd' => ['required', 'numeric', 'min:0.000001'],
                'interest_rate' => ['required', 'numeric'],
                'inflation_rate' => ['required', 'numeric'],
            ]);

            if (!$validator->passes()) {
                return new Response(view('exchange_inflation.edit', ['assessmentId' => $assessmentId, 'rows' => [], 'errors' => $validator->errors]), 422);
            }

            $this->db()->insert('exchange_inflation_rates', [
                'assessment_id' => $assessmentId,
                'year' => (int) $year,
                'etb_per_usd' => (float) $row['etb_per_usd'],
                'usd_per_etb' => round(1 / (float) $row['etb_per_usd'], 8),
                'interest_rate' => (float) $row['interest_rate'],
                'inflation_rate' => (float) $row['inflation_rate'],
                'source_notes' => trim((string) ($notes[$i] ?? '')),
            ]);
        }

        $this->db()->update('module_statuses', ['status' => 'complete', 'updated_at' => date('Y-m-d H:i:s')], 'assessment_id = :id AND module_key = :module_key', ['id' => $assessmentId, 'module_key' => 'exchange_inflation']);
        $this->audit()->log($this->auth()->id(), 'update', 'exchange_inflation', $assessmentId);

        return redirect('/assessments/' . $assessmentId);
    }
}
