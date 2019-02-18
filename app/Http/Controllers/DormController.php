<?php

namespace App\Http\Controllers;

use App\Dorm;
use App\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

class DormController extends Controller
{
    function __construct()
    {
        $this->middleware('auth')->except('showSearchForm', 'doSearchProcess');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(auth()->user()->isAdministrator()) {
            return view('dorms.index');
        }
        else {
            $dorm = Dorm::where('Owner','=',auth()->user()->ID)->first();
            return view('dorms.show', ['data'=>$dorm]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCreateForm()
    {
        return view('dorms.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function doSaveProcess(Request $request)
    {
        $user = new User();
        $user->Username = User::GenerateUsernameForDormitoryUser($request->Name);
        $user->Password = "1234";
        $user->EmailAddress = "default@email.com";
        $user->Name = $request->Owner;
        $user->save();

        $temp = json_decode('['.implode(',',$request->Amenities).']',true);
        $amenities = json_encode($temp);

        $dorm = new Dorm();
        $dorm->Name = $request->Name;
        $dorm->Owner = $user->ID;
        $dorm->AddressLine1 = $request->AddressLine1;
        $dorm->AddressLine2 = $request->AddressLine2;
        $dorm->City = $request->City;
        $dorm->Zip = $request->Zip;
        $dorm->Rate = $request->Rate;
        $dorm->Rooms = $request->Rooms;
        $dorm->MobileNumber = $request->MobileNumber;
        $dorm->LandLineNumber = $request->LandLineNumber;
        $dorm->BusinessPermit = $request->BusinessPermit;
        $dorm->Latitude = $request->Latitude;
        $dorm->Longitude = $request->Longitude;
        $dorm->Amenities = $amenities;
        $dorm->save();

        return redirect()->to('/dorm');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Dorm  $Dorm
     * @return \Illuminate\Http\Response
     */
    public function showDormInformation(Dorm $Dorm)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Dorm  $Dorm
     * @return \Illuminate\Http\Response
     */
    public function showUpdateForm($Dorm)
    {
        $temp = explode('-',$Dorm);
        $dorm = new Dorm();
        $dorm = $dorm->where('ID','=',$temp[0])->first();
        return view('dorms.update', ['data'=>$dorm]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Dorm  $Dorm
     * @return \Illuminate\Http\Response
     */
    public function doUpdateProcess(Request $request, Dorm $dorm)
    {
        $temp = json_decode('['.implode(',',$request->Amenities).']',true);
        $amenities = json_encode($temp);

        $dorm->AddressLine1 = $request->AddressLine1;
        $dorm->AddressLine2 = $request->AddressLine2;
        $dorm->City = $request->City;
        $dorm->Zip = $request->Zip;
        $dorm->Rate = $request->Rate;
        $dorm->Rooms = $request->Rooms;
        $dorm->MobileNumber = $request->MobileNumber;
        $dorm->LandLineNumber = $request->LandLineNumber;
        $dorm->BusinessPermit = $request->BusinessPermit;
        $dorm->Latitude = $request->Latitude;
        $dorm->Longitude = $request->Longitude;
        $dorm->Amenities = $amenities;
        $dorm->save();

        return redirect()->to('/dorm');
    }

    /**
     * Toggles the Status of the specified resource from storage.
     *
     * @param  \App\Dorm  $Dorm
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(Dorm $Dorm)
    {
        //
    }

    /**
     * Fetches all the records from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getAllDormData(Request $request)
    {
        $data = array();
        $dorms = Dorm::all();
        foreach($dorms as $dorm) {
            $entry = array();
            $entry['ID'] = $dorm->ID;
            $entry['Name'] = $dorm->Name;
            $entry['Owner'] = $dorm->getOwner()->Name;
            $entry['Address'] = sprintf("%s, %s, %s", $dorm->AddressLine1, $dorm->AddressLine2, $dorm->City);
            $entry['Mobile'] = $dorm->MobileNumber;
            $entry['Rooms'] = $dorm->Rooms;

            array_push($data, $entry);
        }
        return response()->json(['aaData'=>$data]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Dorm  $dorm
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dorm $dorm)
    {
        //
    }

    public function showSearchForm() {
        return view('search');
    }

    public function testAmenities(Request $request) {
        dd($request);
    }

    public function uploadImage(Request $request)
    {

        $this->validate($request, [
            'Images' => 'required|image|mimes:jpeg,png,jpg,bmp',
        ]);

        if ($request->hasFile('Images')) {
            $image = $request->file('Images');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images');
            $image->move($destinationPath, $name);
            $this->save();

//            return back()->with('success','Image Upload successfully');
        }


        $data = array();
        $data['error'] = "";
        $data['errorkeys'] = [];
        $data['initialPreview'] = [];
        $data['initialPreviewConfig'] = [];
        $data['initialPreviewThumbTags'] = [];
        $data['append'] = true;
        return response()->json($data);
    }

    public function doSearchProcess(Request $request)
    {
        $search = strtolower($request->Search);
        $data = array();
        $dorms = Dorm::all();
        foreach($dorms as $dorm) {
            if (strpos(strtolower($dorm->AddressLine1), $search) !== false) {
                array_push($data, $dorm);
            }
            else if (strpos(strtolower($dorm->AddressLine2), $search) !== false) {
                array_push($data, $dorm);
            }
            else if (strpos(strtolower($dorm->City), $search) !== false) {
                array_push($data, $dorm);
            }
            else if (strpos(strtolower($dorm->Name), $search) !== false) {
                array_push($data, $dorm);
            }
        }
        return view('search',['data'=>$data,'search'=>$search]);
    }
}
