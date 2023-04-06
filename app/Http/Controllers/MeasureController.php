<?php

namespace App\Http\Controllers;

use App\Control;
use App\Domain;
use App\Exports\MeasuresExport;
use App\Measure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MeasureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $measures = Measure::All();
        $domains = Domain::All();

        $domain = $request->get('domain');
        if ($domain !== null) {
            if ($domain === '0') {
                $request->session()->forget('domain');
                $domain = null;
            }
        } else {
            $domain = $request->session()->get('domain');
        }

        if (($domain !== null)) {
            $measures = Measure::where('domain_id', $domain)->get()->sortBy('clause');
            $request->session()->put('domain', $domain);
        } else {
            $measures = Measure::All()->sortBy('clause');
        }

        // return
        return view('measures.index')
            ->with('measures', $measures)
            ->with('domains', $domains);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // get the list of domains
        $domains = Domain::All();

        // get all attributes
        $values = [];
        $attributes = DB::table('attributes')
            ->select('values')
            ->get();
        foreach ($attributes as $attribute) {
            foreach (explode(' ', $attribute->values) as $value) {
                if (strlen($value) > 0) {
                    array_push($values, $value);
                }
            }
            sort($values);
        }

        // store it in the response
        return view('measures.create', compact('values', 'domains'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'domain_id' => 'required',
                'clause' => 'required|min:3|max:30',
                'name' => 'required|min:5',
                'objective' => 'required',
            ]
        );

        $measure = new Measure();
        $measure->domain_id = request('domain_id');
        $measure->clause = request('clause');
        $measure->name = request('name');
        $measure->attributes = request('attributes') !== null ? implode(' ', request('attributes')) : null;
        $measure->objective = request('objective');
        $measure->input = request('input');
        $measure->model = request('model');
        $measure->indicator = request('indicator');
        $measure->action_plan = request('action_plan');
        $measure->owner = request('owner');
        $measure->periodicity = request('periodicity');
        $measure->retention = request('retention');

        $measure->save();

        $request->session()->put('domain', $measure->domain_id);

        return redirect('/measures');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Measure $measure
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Measure $measure)
    {
        return view('measures.show', compact('measure'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Measure $measure
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Measure $measure)
    {
        // get the list of domains
        $domains = Domain::All();

        // get all attributes
        $values = [];
        $attributes = DB::table('attributes')
            ->select('values')
            ->get();
        foreach ($attributes as $attribute) {
            foreach (explode(' ', $attribute->values) as $value) {
                if (strlen($value) > 0) {
                    array_push($values, $value);
                }
            }
        }
        sort($values);

        return view('measures.edit', compact('measure', 'values', 'domains'))->with('domains', $domains);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Measure             $measure
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Measure $measure)
    {
        $this->validate(
            $request,
            [
                'domain_id' => 'required',
                'clause' => 'required|min:3|max:30',
                'name' => 'required|min:5',
                'objective' => 'required',
            ]
        );

        // update measure
        $measure->domain_id = request('domain_id');
        $measure->name = request('name');
        $measure->clause = request('clause');
        $measure->attributes = request('attributes') !== null ? implode(' ', request('attributes')) : null;
        $measure->objective = request('objective');
        $measure->input = request('input');
        $measure->model = request('model');
        $measure->indicator = request('indicator');
        $measure->action_plan = request('action_plan');
        $measure->owner = request('owner');
        $measure->periodicity = request('periodicity');
        $measure->retention = request('retention');

        $measure->save();

        // update the current control
        $control = Control::where('measure_id', $measure->id)
            ->where('realisation_date', null)
            ->get()->first();
        if ($control !== null) {
            $control->clause = $measure->clause;
            $control->name = $measure->name;
            $control->attributes = $measure->attributes;
            $control->objective = $measure->objective;
            $control->input = $measure->input;
            $control->model = $measure->model;
            $control->indicator = $measure->indicator;
            $control->action_plan = $measure->action_plan;
            $control->periodicity = $measure->periodicity;
            $control->retention = $measure->retention;
            $control->save();
        }

        // retun to view measure
        return redirect('/measures/'.$measure->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Measure $measure
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Measure $measure)
    {
        $measure->delete();
        return redirect('/measures');
    }

    /**
     * Plan a measure.
     *
     * @param  \App\Measure $measure
     *
     * @return \Illuminate\Http\Response
     */
    public function plan(Request $request)
    {
        $measure = Measure::find($request->id);

        return view('measures.plan', compact('measure'));
    }

    /**
     * unPlan a measure.
     *
     * @param  \App\Measure $measure
     *
     * @return \Illuminate\Http\Response
     */
    public function unplan(Request $request)
    {
        $control = Control
            ::whereNull('realisation_date')
                ->where('measure_id', '=', $request->id)
                ->get()
                ->first();

        if ($control !== null) {
            // break previous link
            $prev_control = Control::where('next_id', $control->id)->get()->first();
            if ($prev_control !== null) {
                $prev_control->next_id = null;
                $prev_control->update();
            }

            $control->delete();
        }

        return redirect('/measures');
    }

    /**
     * Activate a measure
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request)
    {
        // dd($request);
        $measure = Measure::find($request->id);
        $measure->periodicity = $request->get('periodicity');
        $measure->update();

        // Check control is disabled
        $control = DB::Table('controls')
            ->select('id')
            ->where('measure_id', '=', $measure->id)
            ->where('realisation_date', null)
            ->first();

        if ($control === null) {
            // create a new control
            $control = new Control();
            $control->measure_id = $measure->id;
            $control->domain_id = $measure->domain_id;
            $control->name = $measure->name;
            $control->attributes = $measure->attributes;
            $control->clause = $measure->clause;
            $control->objective = $measure->objective;
            $control->input = $measure->input;
            $control->model = $measure->model;
            $control->indicator = $measure->indicator;
            $control->action_plan = $measure->action_plan;
            $control->owner = $measure->owner;
            $control->periodicity = $measure->periodicity;
            $control->retention = $measure->retention;
            $control->periodicity = $request->get('periodicity');
            $control->plan_date = $request->get('plan_date');
            // Save it
            $control->save();

            // Update link
            $prev_control = Control::where('measure_id', '=', $measure->id)
                ->where('next_id', null)
                ->whereNotNull('realisation_date')
                ->orderBy('id', 'desc')
                ->first();
            if ($prev_control !== null) {
                $prev_control->next_id = $control->id;
                $prev_control->update();
            }
        } else {
            // just update the date
            $control = Control::find($control->id);
            $control->periodicity = $request->get('periodicity');
            $control->plan_date = $request->get('plan_date');
            $control->save();
        }

        // return to the list of measures
        return redirect('/measures');
    }

    /**
     * Disable a measure
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function disable(Request $request)
    {
        $control_id = DB::table('controls')
            ->select('id')
            ->where('measure_id', '=', $request->id)
            ->where('realisation_date', null)
            ->get()
            ->first()->id;
        if ($control_id !== null) {
            // break link
            DB::update('UPDATE controls SET next_id = null WHERE next_id =' . $control_id);
            // delete control
            DB::delete('DELETE FROM controls WHERE id = ' . $control_id);
        }

        // return to the list of measures
        return redirect('/measures');
    }

    public function export()
    {
        return Excel::download(new MeasuresExport(), 'measures.xlsx');
    }
}
