<?php
namespace App\Http\Controllers;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
final class NotificationCenterController extends Controller {
 public function index(Request $request){
  CompanyAuthorization::authorize($request,'notification.view');
  $rows=DB::table('notifications')->where('notifiable_type',User::class)->where('notifiable_id',$request->user()->id)->latest('created_at')->paginate(30);
  return view('notifications.index',compact('rows'));
 }
 public function read(Request $request,string $id){
  CompanyAuthorization::authorize($request,'notification.view');
  DB::table('notifications')->where('id',$id)->where('notifiable_type',User::class)->where('notifiable_id',$request->user()->id)->whereNull('read_at')->update(['read_at'=>now()]);
  return back();
 }
}