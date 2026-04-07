<?php
declare(strict_types=1);

namespace App\Modules\Assessments;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Auth\ScopeService;
use App\Core\Workflow\RevisionComparisonService;
use App\Core\Validation\Validator;
use App\Core\Workflow\WorkflowEngine;

class AssessmentController extends Controller
{
    public function index(): Response
    {
        $userId = (int) $this->auth()->id();
        $scope = $this->container->get(ScopeService::class);
        $params = [];
        $sql = 'SELECT a.*, o.name AS organization_name, fy.label AS fiscal_year_label
                FROM assessments a
                LEFT JOIN organizations o ON o.id = a.organization_id
                LEFT JOIN fiscal_years fy ON fy.id = a.fiscal_year_id';
        if (!$scope->isSuperAdmin($userId)) {
            $organizationIds = $scope->organizationIdsForUser($userId);
            if ($organizationIds === []) {
                $assessments = [];
                return $this->render('assessments.index', compact('assessments'));
            }
            $placeholders = implode(',', array_fill(0, count($organizationIds), '?'));
            $sql .= " WHERE a.organization_id IN ({$placeholders})";
            $params = $organizationIds;
        }
        $sql .= ' ORDER BY a.id DESC';
        $assessments = $this->db()->statement($sql, $params)->fetchAll();
        return $this->render('assessments.index', compact('assessments'));
    }

    public function create(): Response
    {
        $scope = $this->container->get(ScopeService::class);
        $userId = (int) $this->auth()->id();
        if ($scope->isSuperAdmin($userId)) {
            $organizations = $this->db()->all('SELECT * FROM organizations WHERE is_active = 1 ORDER BY name');
        } else {
            $organizationIds = $scope->organizationIdsForUser($userId);
            if ($organizationIds === []) {
                $organizations = [];
            } else {
                $placeholders = implode(',', array_fill(0, count($organizationIds), '?'));
                $organizations = $this->db()->statement("SELECT * FROM organizations WHERE is_active = 1 AND id IN ({$placeholders}) ORDER BY name", $organizationIds)->fetchAll();
            }
        }
        $fiscalYears = $this->db()->all('SELECT * FROM fiscal_years WHERE is_active = 1 ORDER BY label');
        $assessmentPeriods = $this->db()->all('SELECT * FROM assessment_periods WHERE is_active = 1 ORDER BY start_date DESC');
        $facilities = $this->db()->all('SELECT * FROM facilities WHERE is_active = 1 ORDER BY name');
        return $this->render('assessments.create', compact('organizations', 'fiscalYears', 'assessmentPeriods', 'facilities'));
    }

    public function store(): Response
    {
        $data = [
            'title' => trim((string) $this->request->input('title')),
            'organization_id' => (int) $this->request->input('organization_id'),
            'facility_id' => (int) $this->request->input('facility_id', 0),
            'fiscal_year_id' => (int) $this->request->input('fiscal_year_id'),
            'assessment_period' => trim((string) $this->request->input('assessment_period')),
            'metadata' => trim((string) $this->request->input('metadata')),
            'assumptions_notes' => trim((string) $this->request->input('assumptions_notes')),
        ];
        $scope = $this->container->get(ScopeService::class);
        if (!$scope->isSuperAdmin((int) $this->auth()->id()) && !in_array($data['organization_id'], $scope->organizationIdsForUser((int) $this->auth()->id()), true)) {
            return new Response('Forbidden', 403);
        }

        $validator = new Validator($data, [
            'title' => ['required'],
            'organization_id' => ['required', 'numeric'],
            'fiscal_year_id' => ['required', 'numeric'],
            'assessment_period' => ['required'],
        ]);

        if (!$validator->passes()) {
            $organizations = $this->db()->all('SELECT * FROM organizations WHERE is_active = 1 ORDER BY name');
            $fiscalYears = $this->db()->all('SELECT * FROM fiscal_years WHERE is_active = 1 ORDER BY label');
            $assessmentPeriods = $this->db()->all('SELECT * FROM assessment_periods WHERE is_active = 1 ORDER BY start_date DESC');
            $facilities = $this->db()->all('SELECT * FROM facilities WHERE is_active = 1 ORDER BY name');
            return new Response(view('assessments.create', compact('organizations', 'fiscalYears', 'assessmentPeriods', 'facilities') + ['errors' => $validator->errors]), 422);
        }

        $id = $this->db()->insert('assessments', [
            'title' => $data['title'],
            'organization_id' => $data['organization_id'],
            'facility_id' => $data['facility_id'] > 0 ? $data['facility_id'] : null,
            'fiscal_year_id' => $data['fiscal_year_id'],
            'assessment_period' => $data['assessment_period'],
            'assumptions_notes' => $data['assumptions_notes'],
            'metadata_json' => $data['metadata'] !== '' ? json_encode(['metadata' => $data['metadata']], JSON_UNESCAPED_UNICODE) : null,
            'status' => 'draft',
            'calculation_version_id' => 1,
            'current_revision' => 1,
            'created_by' => $this->auth()->id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db()->insert('module_statuses', [
            'assessment_id' => $id,
            'module_key' => 'sample_information',
            'status' => 'Not Started',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db()->insert('module_statuses', [
            'assessment_id' => $id,
            'module_key' => 'exchange_inflation',
            'status' => 'Not Started',
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
        $workflowHistory = $this->db()->all('SELECT * FROM workflow_history WHERE assessment_id = :id ORDER BY id DESC', ['id' => $id]);
        $revisions = $this->db()->all('SELECT * FROM assessment_revisions WHERE assessment_id = :id ORDER BY revision_number DESC', ['id' => $id]);
        return $this->render('assessments.show', compact('assessment', 'moduleStatuses', 'sample', 'exchangeRows', 'workflowHistory', 'revisions'));
    }

    public function submit(): Response
    {
        return $this->transition('submitted', 'submit');
    }

    public function approve(): Response
    {
        return $this->transition('approved', 'approve');
    }

    public function review(): Response
    {
        return $this->transition('reviewed', 'review');
    }

    public function returnForCorrection(): Response
    {
        return $this->transition('returned', 'return');
    }

    public function lock(): Response
    {
        return $this->transition('locked', 'lock');
    }

    public function unlock(): Response
    {
        $reason = trim((string) $this->request->input('reason'));
        if ($reason === '') {
            return new Response('Unlock reason is required.', 422);
        }

        $id = (int) $this->params['id'];
        $assessment = $this->db()->one('SELECT * FROM assessments WHERE id = :id', ['id' => $id]);
        if (!$assessment || $assessment['status'] !== 'locked') {
            return new Response('Only locked assessments can be reopened.', 422);
        }

        $nextRevision = ((int) ($assessment['current_revision'] ?? 1)) + 1;
        $this->db()->update('assessments', [
            'status' => 'returned',
            'current_revision' => $nextRevision,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);

        $this->db()->insert('assessment_revisions', [
            'assessment_id' => $id,
            'revision_number' => $nextRevision,
            'reason' => $reason,
            'created_by' => $this->auth()->id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db()->insert('workflow_history', [
            'assessment_id' => $id,
            'from_status' => 'locked',
            'to_status' => 'returned',
            'acted_by' => $this->auth()->id(),
            'comments' => $reason,
            'metadata_json' => json_encode(['action' => 'unlock_reopen', 'revision_number' => $nextRevision], JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->audit()->log($this->auth()->id(), 'unlock', 'assessment', $id, ['reason' => $reason, 'revision' => $nextRevision]);

        return redirect('/assessments/' . $id);
    }

    public function revisions(): Response
    {
        $id = (int) $this->params['id'];
        $revisions = $this->db()->all('SELECT * FROM assessment_revisions WHERE assessment_id = :id ORDER BY revision_number DESC', ['id' => $id]);
        return $this->render('assessments.revisions', compact('id', 'revisions'));
    }

    public function compareRevisions(): Response
    {
        $id = (int) $this->params['id'];
        $from = (int) $this->request->input('from', 1);
        $to = (int) $this->request->input('to', 1);
        $comparison = $this->container->get(RevisionComparisonService::class)->compare($id, $from, $to);
        return $this->render('assessments.compare', compact('id', 'from', 'to', 'comparison'));
    }

    private function transition(string $to, string $action): Response
    {
        $id = (int) $this->params['id'];
        $comments = trim((string) $this->request->input('comments'));
        $assessment = $this->db()->one('SELECT * FROM assessments WHERE id = :id', ['id' => $id]);
        $engine = new WorkflowEngine();

        if (!$assessment || !$engine->canTransition($assessment['status'], $to)) {
            return new Response('Invalid workflow transition.', 422);
        }
        if ($to === 'returned' && $comments === '') {
            return new Response('Return reason is required.', 422);
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
            'comments' => $comments,
            'metadata_json' => json_encode(['action' => $action], JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit()->log($this->auth()->id(), $action, 'assessment', $id, ['from' => $assessment['status'], 'to' => $to]);

        return redirect('/assessments/' . $id);
    }
}
