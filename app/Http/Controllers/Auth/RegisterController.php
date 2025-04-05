<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Twilio\Rest\Client;



class RegisterController extends Controller
{

    public function sendVerification(Request $request){
        $request->validate([
            'phone_number' => 'required|unique:users',
        ]);
        //setting variables
        $phoneNumber = $request->phone_number;
        $verificationCode= rand(100000, 999999);

        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);
        // storing on table
        $user = User::firstOrNew(['phone_number' => request('phone_number')]);
        $user->otp= $verificationCode;
        $user->otp_expires_at= now()->addMinutes(10);
        $user->save();



        //creatin and sending msg
        $twilio->messages->create(
            "whatsapp:+{$phoneNumber}",
            array(
                'from'=> env('TWILIO_WHATSAPP_NUMBER'),
                'body'=> "Tu Codigo de verificacion es:{$verificationCode}"
            )
        );

    }
    public function verifyAndRegister(Request $request){
     //todo:validate
        $user =User::where('phone_number', $request->phone_number)
            ->where('otp', $request->otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        //logic
        if(!$user){
            return response()->json(['message' => 'Codigo Incorrecto o  Expirado'], 400);
        }
        //clearing otp
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        //register user
        //todo: add fields needed
        $user->name =$request->name;
        $user->save();

        //response
        return response()->json(['message' => 'Registro Exitoso'], 200);
    }
}
