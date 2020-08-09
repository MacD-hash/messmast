<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Message;
use Illuminate\Support\Facades\Auth;
use App\Events\NewMessage;
use DB;

class ContactsController extends Controller
{
    public function get()
    {
        // get all users except the authenticated one
        $contacts = User::where('id','<>',Auth::id())->get();

        $unreadIds = Message::select(DB::raw('`from` as sender_id, count(`from`) as messages_count'))
            ->where('to', Auth::id())
            ->where('read', false)
            ->groupBy('from') //[["sender_id" => 4, "messages_count" => 15],|["sender_id" => 4, "messages_count" => 15],]
            ->get();
        $contacts = $contacts->map(function($contact) use ($unreadIds) {
            $contactUnread = $unreadIds->where('sender_id', $contact->id)->first();

            $contact->unread = $contactUnread ? $contactUnread->messages_count : 0;

            return $contact;
        });
        return response()->json($contacts);
    }
    public function getMessageFor($id)
    {
        // $messages = Message::where('from',$id)->orWhere('to',$id)->get();
        // mark all message with the selected contact as read
        Message::where('from', $id)->where('to', Auth::id())->update(['read' => true]);
        $messages = Message::where(function($q) use ($id) {
            $q->where('from', Auth::id());
            $q->where('to', $id);
        })->orWhere(function($q) use ($id) {
            $q->where('from', $id);
            $q->where('to', Auth::id());
        })->get(); // (a = 1 AND b = 2) or (c = 1 AND d = 2)

        return response()->json($messages);
    }
    public function send(Request $request)
    {
        $message = Message::create([
            'from' => Auth::id(),
            'to' => $request->contact_id,
            'text' => $request->text
        ]);
        broadcast(new NewMessage($message));
        return response()->json($message);
    }
}
