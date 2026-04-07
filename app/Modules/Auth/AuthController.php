<?php
declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Controller;
use App\Core\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return $this->render('auth.login');
    }

    public function login(): Response
    {
        $identity = (string) $this->request->input('identity');
        $password = (string) $this->request->input('password');

        if ($this->auth()->attempt($identity, $password)) {
            $this->audit()->log($this->auth()->id(), 'login', 'user', $this->auth()->id());
            return redirect('/assessments');
        }

        return new Response(view('auth.login', ['error' => 'Invalid credentials']), 422);
    }

    public function logout(): Response
    {
        $userId = $this->auth()->id();
        $this->auth()->logout();
        $this->audit()->log($userId, 'logout', 'user', $userId);
        return redirect('/login');
    }
}
