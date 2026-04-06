<?php
declare(strict_types=1);

namespace App\Modules\Assessments;

use App\Core\Auth\Csrf;
use App\Core\Controller;
use App\Core\Response;
use App\Core\Validation\Validator;
use App\Core\Workflow\WorkflowEngine;

class AssessmentController extends Controller
{
    public function index(): Response
    {
        $assessments = $this->db()->all(
            'SELECT a.*, o.name AS organization_name, fy.label AS fiscal_year_label
             FROM assessments a
             LEFT JOIN organizations o ON o.id = a.organization_id
             LEFT JOIN fiscal_years fy ON fy.id = a.fiscal_year_id
             ORDER BY a.id DESC'
        );
        return $this->render('assessments.index', compact('assessments'));
    }

    public function create(): Response
    {
        $organizations = $this->db()->all('SELECT * FROM organizations WHERE is_active = 1 ORDER BY name');
        $fiscalYears = $this->db()->all('SELECT * FROM fiscal_years WHERE is_active = 1 ORDER BY label');
        return $this->render('assessments.create', compact('organizations', 'fiscalYears'));
    }

    public function store(): Response
    {
        Csrf::validate($this->request->input('_csrf'));
        $data = [
            'title' => trim((string) $this->request->input('title')),
            'organization_id' => (int) $this->request->input('organization_id'),
            'fiscal_year_id' => (int) $this->request->input('fiscal_year_id'),
            'assessment_period' => trim((string) $this->request->input('assessment_period')),
            'assumptions_notes' => trim((string) $this->request->input('assumptions_notes')),
        ];

        $validator = new Validator($data, [
            'title' => ['required'],
            'organization_id' => ['required', 'numeric'],
            'fiscal_year_id' => ['required', 'numeric'],
            'assessment_period' => ['required'],
        ]);

        if (!$validator->passes()) {
            $organizations = $this->db()->all('SELECT * FROM organizations WHERE is_active = 1 ORDER BY name');
            $fiscalYears = $this->db()->all('SELECT * FROM fiscal_years WHERE is_active = 1 ORDER BY label');
            return new Response(view('assessments.create', compact('organizations', 'fiscalYears') + ['errors' => $validator->errors]), 422);
        }

        $id = $this->db()->insert('assessments', [
            'title' => $data['title'],
            'organization_id' => $data['organization_id'],
            'fiscal_year_id' => $data['fiscal_year_id'],
            'assessment_period' => $data['assessment_period'],
            'assumptions_notes' => $data['assumptions_notes'],
            'status' => 'draft',
            'calculation_version_id' => 1,
            'created_by' => $this->auth()->id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db()->insert('module_statuses', [
            'assessment_id' => $id,
            'module_key' => 'sample_information',
            'status' => 'not_started',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db()->insert('module_statuses', [
            'assessment_id' => $id,
            'module_key' => 'exchange_inflation',
            'status' => 'not_started',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit()->log($this->auth()->id(), 'create', 'assessment', $id, $data);

        return redirect('/assessments/' . $id);
    }

    public function show(): Response
    {
        $id = (int) $this->params['id'];
        $assessment = $this->db()->one('SELECT * FROM vw_assessment_overview WHERE assessment_id = :id', ['id' => $id]);
        $moduleStatuses = $this->db()->all('SELECT * FROM module_statuses WHERE assessment_id = :id ORDER BY module_key', ['id' => $id]);
        $sample = $this->db()->one('SELECT * FROM sample_information WHERE assessment_id = :id', ['id' => $id]);
        $exchangeRows = $this->db()->all('SELECT * FROM exchange_inflation_rates WHERE assessment_id = :id ORDER BY year', ['id' => $id]);
        return $this->render('assessments.show', compact('assessment', 'moduleStatuses', 'sample', 'exchangeRows'));
    }

    public function submit(): Response
    {
        return $this->transition('submitted', 'submit');
    }

    public function approve(): Response
    {
        return $this->transition('approved', 'approve');
    }

    public function lock(): Response
    {
        return $this->transition('locked', 'lock');
    }

    private function transition(string $to, string $action): Response
    {
        Csrf::validate($this->request->input('_csrf'));
        $id = (int) $this->params['id'];
        $assessment = $this->db()->one('SELECT * FROM assessments WHERE id = :id', ['id' => $id]);
        $engine = new WorkflowEngine();

        if (!$assessment || !$engine->canTransition($assessment['status'], $to)) {
            return new Response('Invalid workflow transition.', 422);
        }

        $this->db()->update('assessments', [
            'status' => $to,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);

        $this->db()->insert('workflow_history', [
            'assessment_id' => $id,
            'from_status' => $assessment['status'],
            'to_status' => $to,
            'acted_by' => $this->auth()->id(),
            'comments' => trim((string) $this->request->input('comments')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit()->log($this->auth()->id(), $action, 'assessment', $id, ['from' => $assessment['status'], 'to' => $to]);

        return redirect('/assessments/' . $id);
    }
}
