<?php

namespace App\Http\Controllers;

use App\HelperFunctions\InvitationHelper;
use App\HelperFunctions\UserPermissions;
use App\Models\Audit;
use App\Models\Invitation;
use App\Notifications\NotifyUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class InvitationManagementController extends Controller
{
    public function management()
    {
        return view('invitation.management');
    }

    public function list()
    {
        $invitations = Invitation::getInvitations();
        
        return response()->json(['data' => $invitations], 200);
    }

    public function createInvitation()
    {
        return view('ajax.invitation-create', [
            'countries' => UserPermissions::getUserCountries('name'),
            'roles' => UserPermissions::getUserRoles('name')
        ]);
    }

    public function storeInvitation(Request $request)
    {
        $inputs = $request->all();
        $inputs['name'] = $inputs['firstname'] . ' ' . $inputs['lastname'];
        
        $validator = InvitationHelper::validateInputsForCreate($inputs);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages(), 'type' => 'pageModalForm'], 400);
        }

        $resp = InvitationHelper::canInviteUser($inputs);
        if ($resp['type'] == 'error') {
            return response()->json([$resp['type'] => $resp['msg'], 'type' => 'pageAlert'], $resp['status']);
        }

        Invitation::disableAuditing();
        $invitation = InvitationHelper::storeInvitation($inputs);
        Invitation::enableAuditing();
        
        Audit::setCustomAuditEvent(
            Invitation::find($invitation->id),
            [
                'event' => 'created', 'audit' => [
                    'name' => $inputs['name'],
                    'email' => $inputs['email'],
                    'role' => $inputs['role'],
                    'country' => $inputs['country']
                ]
            ]
        );

        Notification::route('mail', [
            $inputs['email'] => $inputs['name']
        ])->notify(new NotifyUser([
            'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
            'subject' => 'Invitation to join the EUCSI Platform',
            'markdown' => 'registration-user-invitation',
            'maildata' => [
                'url' => env('APP_URL') . '?hash=' . $invitation->hash,
                'name' => $inputs['name'],
                'author' => Auth::user()->isAdmin() ? config('constants.USER_GROUP') : Auth::user()->name,
                'deadline' => env('REGISTRATION_DEADLINE')
            ]
        ]));

        return response()->json('ok', 200);
    }
}
