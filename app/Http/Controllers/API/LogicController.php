<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Pikirasa\RSA;
use Illuminate\Support\Facades\URL;
use Illuminate\Contracts\Encryption\DecryptException;

// model
use App\Secret;

class LogicController extends Controller
{
    public $successStatus = 200;
    public $vaildStatus = 401;

    private $serverKey = '';

    /**
     * Return the signed URL of the specified user
     * 
     * $return signed url
     */
    // public function getSignedUrl() {
    //     echo URL::signedRoute('storeSecret');
    // }

    /**
     * register API
     * 
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request) {

        $input = array();
        if (!$request->input('username')) {
            return response()->json(['failure' => 'username parameter error'], $this->vaildStatus);
        }

        if (!$request->input('publicKey')) {
            return response()->json(['failure' => 'publicKey parameter error'], $this->vaildStatus);
        }

        $input['username'] = $request->input('username');
        $input['publicKey'] = $request->input('publicKey');

        // if user not exist, return false.
        $sec_id = Secret::getUserId($input);

        if ($sec_id) {
            return response()->json(['failure' => 'The user already exist.'], $this->vaildStatus);
        }

        $ret = Secret::insertUserData($input);
        
        if (!$ret) {
            return response()->json(['failure' => 'Registeration failed.'], $this->vaildStatus);
        }

        return response()->json(['success' => $ret], $this->successStatus);
    }

    /**
     * getServerKey API
     * 
     * @return \Illuminate\Http\Response
     */
    public function getServerKey() {
        // retrieves the server public key
        $this->serverKey = config('app.key');

        return response()->json(['success' => $this->serverKey], $this->successStatus);
    }

    private function validateStoreSignature($request) {
        $input['publicKey'] = $request->input('key');
        $input['username'] = $request->input('username');

        return Secret::validateStoreSignature($input);
    }

    /**
     * storeSecret API
     * 
     * @return \Illuminate\Http\Response
     */
    public function storeSecret(Request $request) {

        if (!$this->validateStoreSignature($request)) {
            return response()->json(['failure' => 'Request Vailidation failed.'], $this->vaildStatus);
        }

        if (!$request->input('username')) {
            return response()->json(['failure' => 'username parameter error'], $this->vaildStatus);
        }
        $input['username'] = $request->input('username');

        if (!$request->input('secretName')) {
            return response()->json(['failure' => 'secretName parameter error'], $this->vaildStatus);
        }
        $input['secretName'] = $request->input('secretName');

        if (!$request->input('encryptedSecret')) {
            return response()->json(['failure' => 'encryptedSecret parameter error'], $this->vaildStatus);
        }
        $input['encryptedSecret'] = $request->input('encryptedSecret');
        
        $sec_id = Secret::getUserId($input);

        if (!$sec_id) {
            return response()->json(['failure' => 'No User. Please register first.'], $this->vaildStatus);
        }

        try {
            $decryptedMessage = decrypt($input['encryptedSecret']);
        } catch (DecryptException $e) {
            return response()->json(['failure' => 'Descryption failed'], $this->vaildStatus);
        }

        $input['sec_id'] = $sec_id;

        $ret = Secret::insertMessageData($input);

        if (!$ret) {
            return response()->json(['failure' => 'Failed.'], $this->vaildStatus);
        }

        return response()->json(['success' => $ret], $this->successStatus);
    }

    /**
     * getSecret API
     * 
     * @return \Illuminate\Http\Response
     */
    public function getSecret(Request $request) {
        if (!$request->input('username')) {
            return response()->json(['failure' => 'username parameter error'], $this->vaildStatus);
        }
        $input['username'] = $request->input('username');

        if (!$request->input('secretName')) {
            return response()->json(['failure' => 'secretName parameter error'], $this->vaildStatus);
        }
        $input['secretName'] = $request->input('secretName');

        $sec_id = Secret::getUserId($input);

        if (!$sec_id) {
            return response()->json(['failure' => 'No User. Please register first.'], $this->vaildStatus);
        }

        $messages = Secret::getMessage($input);

        if (!$messages) {
            return response()->json(['failure' => 'No message'], $this->vaildStatus);
        }

        $key = $this->serverKey;
        $ret = '';

        try {
            $ret = decrypt($messages->encryptedSecret);
        } catch (DecryptException $e) {
            return response()->json(['failure' => 'Descryption failed'], $this->vaildStatus);
        }

        $publicKey = str_replace("\\n", "\n", $messages->publicKey);
        
        $rsa = new RSA($publicKey);
        $encryptedMessage = $rsa->base64Encrypt($ret);

        return response()->json(['success' => $encryptedMessage], $this->successStatus);
    }
}