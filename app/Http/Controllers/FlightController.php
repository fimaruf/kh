<?php

namespace App\Http\Controllers;

use App\Http\Resources\Flight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FlightController extends Controller
{
    protected $flights;

    public function __construct()
    {
        $files = Storage::Files('flights'); // all json files are in Storage/flights folder
        $flights = array();

        if ($files) {
            foreach ($files as $file) {
                $ext = substr($file, strlen($file)-4, strlen($file));
                if (strtolower($ext) === 'json') { // only json file will be considered
                    $content = json_decode(file_get_contents(storage_path() . '/' . $file), true);
                    array_push($flights, $content);
                }
            }
        } // else it will set an empty array as result

        $this->flights = collect($flights);
    }

    /**
     * List of flights
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request) {
        return Flight::collection(
            $this->flights
        );
    }

    /**
     * Flight specific data
     *
     * @param $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function show($id)
    {
        return Flight::collection(
            $this->flights->where('uuid', $id), 1
        );
    }
}
