<?php
declare(strict_types=1);

namespace App\Modules\SampleInformation;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Validation\Validator;
use App\Core\Audit\ChangeTracker;
use App\Core\Workflow\WorkflowEngine;

class SampleInformationController extends Controller
{
    public function edit(): Response
    {
        $assessmentId = (int) $this->params['id'];
        $this->db()->update('module_statuses', ['status' => 'In Progress', 'updated_at' => date('Y-m-d H:i:s')], 'assessment_id = :id AND module_key = :module_key AND status = :status', ['id' => $assessmentId, 'module_key' => 'sample_information', 'status' => 'Not Started']);
        $record = $this->db()->one('SELECT * FROM sample_information WHERE assessment_id = :id', ['id' => $assessmentId]);
        return $this->render('sample_information.edit', compact('assessmentId', 'record'));
    }

    public function update(): Response
    {
        $assessmentId = (int) $this->params['id'];
        $assessment = $this->db()->one('SELECT status, current_revision FROM assessments WHERE id = :id', ['id' => $assessmentId]);
        $engine = new WorkflowEngine();
        if (!$assessment || !$engine->canEdit($assessment['status'])) {
            return new Response('Assessment is read-only in its current workflow state.', 422);
        }

        $data = [
            'sites_surveyed' => $this->request->input('sites_surveyed'),
            'sites_total' => $this->request->input('sites_total'),
            'central_units' => $this->request->input('central_units'),
            'hubs' => $this->request->input('hubs'),
            'notes' => trim((string) $this->request->input('notes')),
        ];

        $validator = new Validator($data, [
            'sites_surveyed' => ['required', 'numeric', 'min:0'],
            'sites_total' => ['required', 'numeric', 'min:0', 'gte:sites_surveyed'],
            'central_units' => ['required', 'numeric', 'min:0'],
            'hubs' => ['required', 'numeric', 'min:0'],
        ]);

        if (!$validator->passes()) {
            $this->db()->update('module_statuses', ['status' => 'Validation Errors', 'updated_at' => date('Y-m-d H:i:s')], 'assessment_id = :id AND module_key = :module_key', ['id' => $assessmentId, 'module_key' => 'sample_information']);
            return new Response(view('sample_information.edit', ['assessmentId' => $assessmentId, 'record' => $data, 'errors' => $validator->errors]), 422);
        }

        $existing = $this->db()->one('SELECT * FROM sample_information WHERE assessment_id = :id', ['id' => $assessmentId]);
        if ($existing) {
            $this->db()->update('sample_information', [
                'sites_surveyed' => $data['sites_surveyed'],
                'sites_total' => $data['sites_total'],
                'central_units' => $data['central_units'],
                'hubs' => $data['hubs'],
                'notes' => $data['notes'],
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'assessment_id = :id', ['id' => $assessmentId]);
        } else {
            $this->db()->insert('sample_information', [
                'assessment_id' => $assessmentId,
                'sites_surveyed' => $data['sites_surveyed'],
                'sites_total' => $data['sites_total'],
                'central_units' => $data['central_units'],
                'hubs' => $data['hubs'],
                'notes' => $data['notes'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db()->update('module_statuses', ['status' => 'Complete', 'updated_at' => date('Y-m-d H:i:s')], 'assessment_id = :id AND module_key = :module_key', ['id' => $assessmentId, 'module_key' => 'sample_information']);
        $this->container->get(ChangeTracker::class)->track($assessmentId, 'sample_information', 'bulk_update', json_encode($existing), json_encode($data), $this->auth()->id(), (int) ($assessment['current_revision'] ?? 1), trim((string) $this->request->input('change_reason')));
        $this->audit()->log($this->auth()->id(), 'update', 'sample_information', $assessmentId, $data);

        return redirect('/assessments/' . $assessmentId);
    }
}
