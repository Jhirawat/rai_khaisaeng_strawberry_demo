<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->filled('action'), fn($q)=>$q->where('action',$request->action))
            ->when($request->filled('search'), function($q) use($request){
                $s = trim($request->search);
                $q->where(function($qq) use($s){
                    $qq->where('subject_label','like',"%{$s}%")
                       ->orWhere('action','like',"%{$s}%")
                       ->orWhereHas('user', fn($u)=>$u->where('name','like',"%{$s}%")->orWhere('email','like',"%{$s}%"));
                });
            })
            ->latest()->paginate(30)->withQueryString();
        $actions = ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        return view('admin.activity-logs.index', compact('logs','actions'));
    }
}
