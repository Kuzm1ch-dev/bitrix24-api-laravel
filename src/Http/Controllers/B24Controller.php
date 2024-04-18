<?php

namespace B24Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use \B24Api\Classes\QueryStatMonth;
use Illuminate\Support\Facades\Auth;

class B24Controller extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected string $memberId;
    protected int $userId;
    protected string $eventToken;

    public function __construct(Request $request)
    {
        if ($request->has('event_token')) {
            $this->eventToken = $request->post('event_token');
        }
		if(Auth::check() || (Auth::user())){
			$this->userId = Auth::user()->user_id;
		}
		
        if ($request->has('user_id') && $request->post('user_id')) {
            $this->userId = $request->post('user_id');
        }
        if ($request->has('member_id') && $request->post('member_id')) {
            $this->memberId = $request->post('member_id');
        } elseif ($request->has('auth') && $request->post('auth')['member_id']) {
            $this->memberId = $request->post('auth')['member_id'];
        } elseif ($request->has('auth') && $request->json('auth')['member_id']) {
            $this->memberId = $request->json('auth')['member_id'];
        } elseif ($request->hasHeader('x-b24-member-id') && $request->header('x-b24-member-id')) {
            $this->memberId = $request->header('x-b24-Member-id');
        } else {
            throw new \Exception('memberId is null');
        }

        if ($this->memberId) {
            QueryStatMonth::add($this->memberId);
        }
    }

    public function getEventToken(): ?string
    {
        return $this->eventToken;
    }

    // public function index(Request $request): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application
    // {
    //     return view('b24api/index', []);
    // }

    /**
     * @return array|mixed|string|null
     */
    public function getMemberId(): mixed
    {
        return $this->memberId;
    }
}
