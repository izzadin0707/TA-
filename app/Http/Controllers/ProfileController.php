<?php

namespace App\Http\Controllers;

use App\Models\Assets;
use App\Models\Banned;
use App\Models\Comments;
use App\Models\Creations;
use App\Models\Event;
use App\Models\Likes;
use App\Models\Saves;
use App\Models\Users;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function validateBan(){
        if(Banned::where('user_id', Auth::id())->first() == null){
            return true;
        }else{
            Auth::logout();
            return redirect('/')->withErrors(['email' => 'You Account Status Is Banned!']);
        }
    }
    
    public function index($tab = null){
        if($this->validateBan()){
            $page = $tab == null ? 'posting' : $tab;
            $creation = Creations::with(['users', 'categorys']);
            // $creation = Creations::with(['users', 'categorys'])->where('user_id', Auth::id());

            return view('profile', [
                "page" => $page,
                "auth_assets" => Assets::where('user_id', Auth::id())->get(),
                "assets" => Assets::all(),
                "user" => Auth::user(),
                "eventsAll" => Event::all(),
                "creations" => $creation->latest()->get(),
                "likes" => Likes::all(),
                "saves" => Saves::all(),
                "comments" => Comments::with('creations.categorys')->latest()->get(),
            ]);
        }
    }

    public function profile($username){
        if($username != Auth::user()->username){
            $user = Users::where('username', $username)->firstOrFail();
            $posts = Creations::where('user_id', $user->id)->count();
            $likes = Likes::where('creation_user_id', $user->id)->count();
            return view('profile', [
                "user" => $user,
                "color" => $user->color,
                "font" => $user->font,
                "creations" => Creations::with(['categorys'])->where('user_id', $user->id)->latest()->get(),
                "auth_assets" => Assets::where('user_id', Auth::id())->get(),
                "assets" => Assets::where('user_id', $user->id)->get(),
                "posts" => $posts,
                "likes" => $likes
            ]);
        }else{
            return redirect()->to('profile');
        }
    }

    public function profileSetting(){
        return view('profile-setting', [
            "user" => Auth::user(),
            "color" => Auth::user()->color,
            "font" => Auth::user()->font,
            "auth_assets" => Assets::where('user_id', Auth::id())->get()
        ]);
    }

    public function photoProfile(Request $request) {
        if($request->hasFile('photo-profile')){
            $file = $request->file('photo-profile');
            $mimeType = $file->getMimeType();
            $photoProfile = date('Ymd') . '0' . Auth::id();

            if (Str::startsWith($mimeType, 'image')) {
                $photoProfileEdit = Users::photoProfile($photoProfile);
                $file->move(public_path('storage/assets'), $photoProfileEdit.'.png');
                return $photoProfileEdit;
            }
            return false;
                
        }
        return false;
    }

    public function banner(Request $request) {
        if($request->hasFile('banner-file-input')){
            $file = $request->file('banner-file-input');
            $mimeType = $file->getMimeType();
            $banner = date('Ymd') . '0' . Auth::id();

            if (Str::startsWith($mimeType, 'image')) {
                $bannerEdit = Users::banner($banner);
                $file->move(public_path('storage/assets'), $bannerEdit.'.png');
                return $bannerEdit;
            }
            return false;
                
        }
        return false;
    }

    public function changeName(Request $request) {
        if($request->input('name')){
            $name = $request->input('name');
            $id = Auth::id();
            $username = $name . "#" . str_pad($id, 4, '0', STR_PAD_LEFT);
            
            Users::where('id', $id)->update(['name' => $name, 'username' => $username]);

            return true;
        }
        return false;
    }

    public function backgroundColor(Request $request) {
        if($request->input('color')){
            $color = $request->input('color');
            $id = Auth::id();
            
            Users::where('id', $id)->update(['color' => $color]);

            return true;
        }
        return false;
    }

    public function fontColor(Request $request) {
        if($request->input('color')){
            $color = $request->input('color');
            $id = Auth::id();
            
            Users::where('id', $id)->update(['font' => $color]);

            return true;
        }
        return false;
    }

    public function changePassword(Request $request) {
            $request->validate([
                'password' => 'required',
                'new_password' => 'required|string|min:8|',
                'password_confirmation' => 'required|string|min:8|same:new_password',
            ], [
                'password.required' => 'Password wajib diisi.',
                'new_password.required' => 'Password baru wajib diisi.',
                'new_password.min' => 'Password baru minimal 8 karakter.',
                'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
                'password_confirmation.same' => 'Konfirmasi password tidak cocok.'
            ]);

            
            if(!Hash::check($request->password, auth()->user()->password)){
                return redirect()->back()->with('status', 'Password Wrong!');
            }

            Users::whereId(auth()->user()->id)->update([
                'password' => Hash::make($request->new_password)
            ]);

            return redirect()->back()->with('status', 'success change password!');
    }

    public function resetTheme(Request $request) {
        $id = Auth::id();
        if(Users::where('id', $id)->update(['color' => null, 'font' => null])){
            return true;
        }else{
            return false;
        }
    }
}
