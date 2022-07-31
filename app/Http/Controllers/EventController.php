<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Event;
use App\Models\User;

class EventController extends Controller
{

    //a rota para as views são passadas dentro do controller
    public function index()
    {
        $search = request('search');
        if ($search) {
            $events = Event::where('title', 'like', "%{$search}%")->get();
        } else {
            $events = Event::all();
        }

        return view('welcome', compact('events', 'search')); //compact - array com os dados de events
    }

    public function create()
    {
        return view('events.create'); //devolve a view events.create
    }

    public function store(Request $request)
    {
        $event = new Event;

        $event->title = $request->title;
        $event->date = $request->date;
        $event->description = $request->description;
        $event->city = $request->city;
        $event->private = $request->private;
        $event->items = $request->items;

        // Image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $requestImage = $request->image;
            $extension = $requestImage->extension();
            $imageName = md5($requestImage->getClientOriginalName() . strtotime('now')) . "." . $extension;
            $requestImage->move(public_path('img/events'), $imageName);
            $event->image = $imageName;
        }

        $user = auth()->user(); //pega o usuário logado
        $event->user_id = $user->id; //pega o id do usuário logado

        $event->save(); //função herdada do Model Event

        //Event::create($request->all());

        return redirect()->route('home')->with('msg', 'Evento criado com sucesso!'); //redirect - redireciona para a rota home
    }

    public function show($id)
    {
        $event = Event::findOrFail($id);

        //checagem se o usuário já está participando do evento
        $user = auth()->user();
        $hasUserJoined = false;

        if($user){
            $userEvents = $user->eventsAsParticipant->toArray();

            foreach ($userEvents as $userEvent) {
                if($userEvent['id'] == $event->id){
                    $hasUserJoined = true;
                }
            }
        }

        $eventOwner = User::where('id', $event->user_id)->first()->toArray();
        return view('events.show', compact('event', 'eventOwner', 'hasUserJoined')); //compact - array com os dados de event, eventOwner e hasUserJoined
    }

    public function dashboard()
    {
        $user = auth()->user();
        $events = $user->events; //eventos q o usuário é dono
        $eventsAsParticipant = $user->eventsAsParticipant; //eventos q o usuário participa
        return view('events.dashboard', compact('events', 'eventsAsParticipant'));
    }

    public function destroy($id)
    {
        Event::findOrFail($id)->delete();
        return redirect('/dashboard')->with('msg', 'Evento excluído com sucesso!');
    }

    public function edit($id)
    {
        //medida de segurança: verifica se o usuário logado é o dono do evento
        $user = auth()->user();

        $event = Event::findOrFail($id);

        if ($user->id != $event->user_id) {
            return redirect('/dashboard')->with('msg', 'Você não tem permissão para editar este evento!');
        }

        return view('events.edit', compact('event'));
    }

    public function update(Request $request)
    {

        $data = $request->all();

        // Image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $requestImage = $request->image;
            $extension = $requestImage->extension();
            $imageName = md5($requestImage->getClientOriginalName() . strtotime('now')) . "." . $extension;
            $requestImage->move(public_path('img/events'), $imageName);
            $data['image'] = $imageName;
        }


        Event::findOrFail($request->id)->update($data);

        return redirect('/dashboard')->with('msg', 'Evento editado com sucesso!');
    }

    public function joinEvent($id)
    {
        $event = Event::findOrFail($id);
        $user = auth()->user();
        $event->users()->attach($user->id);
        return redirect('/dashboard')->with('msg', 'Presença confirmada no evento: ' . $event->title);
    }

    public function leaveEvent($id)
    {
        $event = Event::findOrFail($id);
        $user = auth()->user();
        $event->users()->detach($user->id);
        return redirect('/dashboard')->with('msg', 'Você não está mais inscrito no evento: ' . $event->title);
    }
}
