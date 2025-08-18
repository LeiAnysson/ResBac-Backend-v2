<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Peterujah\Agora\Agora;
use Peterujah\Agora\User;
use Peterujah\Agora\Roles;
use Peterujah\Agora\Builders\RtcToken;

class AgoraController extends Controller
{
    public function generateToken(Request $request)
    {
        $channelName = $request->get('channel', 'resbac_channel');
        $uid = intval($request->get('uid', 0));
        $expirySeconds = intval($request->get('expiry', 3600));

        $appID = env('AGORA_APP_ID');
        $appCertificate = env('AGORA_APP_CERTIFICATE');

        if (empty($appCertificate)) {
            return response()->json([
                'token' => null,
                'channelName' => $channelName,
                'uid' => $uid,
                'note' => 'Testing mode — tokens not required.'
            ]);
        }

        $currentTimestamp = now()->timestamp;
        $privilegeExpiredTs = $currentTimestamp + $expirySeconds;

        $client = new Agora($appID, $appCertificate);
        $client->setExpiration($privilegeExpiredTs);

        $user = (new User($uid))
            ->setChannel($channelName)
            ->setRole(Roles::RTC_ATTENDEE)
            ->setPrivilegeExpire($privilegeExpiredTs);

        $token = RtcToken::buildTokenWithUid($client, $user);

        return response()->json([
            'token' => $token,
            'channelName' => $channelName,
            'uid' => $uid,
            'expires_at' => $privilegeExpiredTs
        ]);
    }
}
