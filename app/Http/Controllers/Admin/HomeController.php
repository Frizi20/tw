<?php

namespace App\Http\Controllers\Admin;

use App\Models\SurveyResult;
use App\Models\Dimensiune;
use App\Models\CategorieDeControl;
use App\Models\Departamente;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class HomeController
{
    public function index()
    {

        $departaments =  Departamente::pluck('nume', 'id');

        return view('home', compact('departaments'));
    }

    public function getCategoriesResults(Request $request)
    {

        $getAll = isset($request->all) && $request->all === 'true';
        $depId = $request->depId ?? '';
        $dimId = $request->dimId ?? '';

        $categories = DB::table('categorie_de_controls')
            ->whereNull('categorie_de_controls.deleted_at')
            ->leftJoin('survey_builders as sb', function ($join) use ($depId, $dimId, $getAll) {
                $join->on('sb.categorie_de_control_id', '=', 'categorie_de_controls.id');

                // Get results by depatament
                if($depId !== 'all'){
                    if ($depId) {
                        $join->where('sb.departamente_id', '=', $depId);
                    }

                    if($dimId){
                        $join->where('sb.dimensiune_id','=',$dimId);
                    }
                }
            })

            // ->leftJoin('initiatives', 'category_initiative.initiative_id', '=', 'initiatives.id')
            // ->leftJoin('initiativegroups', 'initiatives.initiativegroup_id', '=', 'initiativegroups.id')
            // ->where('categories.id', '=', 40)
            ->leftJoin('survey_results as sr', function ($join) use ($getAll) {
                $join->on('sr.survey_builder_id', '=', 'sb.id');


                // Get results by user
                // $join->where('sr.user_id', '=', Auth::user()->id);
            })
            ->leftJoin('users as u', 'u.id', '=', 'sr.user_id')
            ->leftJoin('dimensiunes as dim','dim.id','=','sb.dimensiune_id')
            ->select([
                'categorie_de_controls.id as cat_id',
                'dim.dimensiune as dim_name',
                'categorie_de_controls.nume as cat_name',
                'sb.id as sb_id',
                'sr.id as sr_id',
                'sb.*',
                'sr.*',
                'u.name as user_name',
                'u.email as user_email',
                'sb.dimensiune_id as dim_id'
            ])
            ->orderBy('cat_id')
            ->orderBy('dim_name')
            ->get();

        // return response()->json($categories);


        $data = [];

        foreach ($categories as $category) {

            if (!isset($data[$category->cat_id])) {
                $data[$category->cat_id] = [
                    'id'             => $category->cat_id,
                    'name'            => $category->cat_name,
                    'survey_results' => [],
                ];
            }

            if ($category->sr_id) {
                $data[$category->cat_id]['survey_results'][] = [
                    'id' => $category->sr_id,
                    'schema'     => $category->schema_results,
                    'user_name'  => $category->user_name,
                    'dim_name'   => $category->dim_name
                ];
            }
        }

        return response()->json($data);
    }


    public function getDepartamentsResults()
    {
        $departaments = DB::table('departamentes')
            ->select([
                'departamentes.*',
                'departamentes.id as dep_id',
                'sr.id as sr_id',
                'dim.*',
                'sr.*'
            ])
            ->leftJoin('survey_builders as sb', function ($join) {
                $join->on('sb.departamente_id', '=', 'departamentes.id');

                // Get results by depatament
                // if($depId){
                // 	$join->where('sb.departamente_id','=', $depId);
                // }
            })
            ->leftJoin('survey_results as sr', function ($join) {
                $join->on('sr.survey_builder_id', '=', 'sb.id');


                // Get results by user
                // $join->where('sr.user_id', '=', Auth::user()->id);
            })
            ->whereNull('departamentes.deleted_at')
            ->leftJoin('dimensiunes as dim', function($join){
                $join->on('dim.id', '=', 'departamentes.id');
                $join->whereNull('dim.deleted_at');
            })
            ->get();

        // return response()->json($departaments);

        $data = [];

        foreach ($departaments as $departament) {

            if (!isset($data[$departament->dep_id])) {
                $data[$departament->dep_id] = [
                    'id'             => $departament->dep_id,
                    'name'            => $departament->nume,
                    'survey_results' => []
                ];
            }

            if ($departament->sr_id) {
                $data[$departament->dep_id]['survey_results'][] = [
                    'id' => $departament->sr_id,
                    'schema' => $departament->schema_results,
                    // 'user_name'  => $departament->user_name
                ];
            }
        }

        return response()->json($data);
    }

    public function getDimensionsResults(Request $request)
    {

        $depId = $request->depId;

        $dimensions = DB::table('departamentes_dimensiunes')
        // ->where('dep.id', '=', $depId)
        ->where(function($query) use($depId){
            if ($depId) {
                $query->where('dep.id','=', $depId);
            }// $join->on('sr.survey_builder_id', '=', 'sb.id');

        })
        ->leftJoin('departamentes as dep', 'departamentes_dimensiunes.departament_id', '=', 'dep.id')
        ->leftJoin('dimensiunes as dim', 'departamentes_dimensiunes.dimensiune_id', '=', 'dim.id')
        ->whereNull('dim.deleted_at')
        ->select(
            'dim.id as dim_id',
            'sr.id as sr_id',
            'sb.id as sb_id',
            'dep.*',
            'dim.*',
            'dep.id as dep_id',
            'sr.*',
            // 'sb.id as sb_id'
            // 'dim.id as dim_id',
            'u.name as user_name',
            // 'sb.*'
        )
        ->leftJoin('survey_builders as sb', function ($join) {
            $join->on('sb.dimensiune_id', '=', 'dim.id');
            $join->on('sb.departamente_id', '=', 'dep.id');
            // $join->where('sb.dimensiune_id', '=', 3);

        })
        ->leftJoin('survey_results as sr', function ($join) {
            $join->on('sr.survey_builder_id', '=', 'sb.id');



            // Get results by user
            // $join->where('sr.user_id', '=', Auth::user()->id);
        })
        ->leftJoin('users as u', 'u.id', '=', 'sr.user_id')
        ->orderBy('dim_id')
        ->get();

        $data = [];

        foreach ($dimensions as $dimension) {

            if (!isset($data[$dimension->dim_id])) {
                $data[$dimension->dim_id] = [
                    'id'             => $dimension->dim_id,
                    'name'            => $dimension->dimensiune,
                    'survey_results' => []
                ];
            }

            if ($dimension->sr_id) {
                $data[$dimension->dim_id]['survey_results'][] = [
                    'id' => $dimension->sr_id,
                    'schema' => $dimension->schema_results,
                    'user_name'  => $dimension->user_name
                ];
            }
        }

        return response()->json($data);


        if (false) {


            $dimensions = DB::table('dimensiunes')
                ->select([
                    'dimensiunes.id as dim_id',
                    'dimensiunes.*',
                    'sr.*',
                    'sr.id as sr_id',
                    'u.name as user_name',
                    'sb.*'

                ])
                ->leftJoin('departamentes', function ($join) {
                    $join->on('departamentes_dimensiunes.departament_id', '=', '.departamentes_dimensiunesdimensiune_id');
                })
                ->get();

            return response()->json($dimensions);
            die();
            return 's';
            // ->where('departamente_id','=', 1)
            leftJoin('survey_builders as sb', function ($join) {
                $join->on('sb.dimensiune_id', '=', 'dimensiunes.id');
                // $join->where('sb.dimensiune_id', '=', 3);

            })
                ->leftJoin('survey_results as sr', function ($join) {
                    $join->on('sr.survey_builder_id', '=', 'sb.id');



                    // Get results by user
                    // $join->where('sr.user_id', '=', Auth::user()->id);
                })
                ->leftJoin('users as u', 'u.id', '=', 'sr.user_id')

                // ->leftJoin('survey_builders as sb', function ($join)  {
                //     $join->on('sb.departamente_id', '=', 'departamentes.id');

                //     // Get results by depatament
                //     // if($depId){
                //     // 	$join->where('sb.departamente_id','=', $depId);
                //     // }
                // })
                // ->leftJoin('survey_results as sr', function ($join)  {
                //     $join->on('sr.survey_builder_id', '=', 'sb.id');


                //     // Get results by user
                //     // $join->where('sr.user_id', '=', Auth::user()->id);
                // })
                // ->whereNull('departamentes.deleted_at')
                // ->leftJoin('dimensiunes as dim', 'dim.id', '=', 'departamentes.id')
                // ->where('departamente_id','=',1)
                ->orderBy('dim_id')
                ->get();



            // return response()->json($dimensions);

            $data = [];

            foreach ($dimensions as $dimension) {

                if (!isset($data[$dimension->dim_id])) {
                    $data[$dimension->dim_id] = [
                        'id'             => $dimension->dim_id,
                        'name'            => $dimension->dimensiune,
                        'survey_results' => []
                    ];
                }

                if ($dimension->sr_id) {
                    $data[$dimension->dim_id]['survey_results'][] = [
                        'id' => $dimension->sr_id,
                        'schema' => $dimension->schema_results,
                        'user_name'  => $dimension->user_name
                    ];
                }
            }


            return response()->json($data);
        }
    }
}
