<?php


namespace Modules\User\Controllers;


use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{

    public function update(Request $request)
    {
        $token = ApiTokenController::updateUserHash( $request->user() );
        return ['api_token' => $token];
    }

    public static function updateUserHash(User $user) {
        $token = Str::random(60);

        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return $token;
    }
}
