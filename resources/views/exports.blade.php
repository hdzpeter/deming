@extends("layout")

@section("content")
<div class="p-3">
    <div data-role="panel" data-title-caption="{{ trans('cruds.exports.index') }}" data-collapsible="true" data-title-icon="<span class='mif-chart-line'></span>">

		<b>Rapports</b>

		@if (count($errors))
		<div class= “form-group”>
			<div class= “alert alert-danger”>
				<ul>
				@foreach ($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
				</ul>
			</div>
		</div>
		@endif

		<form action="/reports/pilotage" target="_new">

		<div class="grid">
	    	<div class="row">
	    		<div class="cell-5">
				Pilotage du SMSI
				</div>
			</div>

			<div class="row">
		        <div class="cell-1">
					Début
				</div>
		        <div class="cell-2">
		            <input type="text"
		                    data-role="calendarpicker" 
		                    name="start_date" 
		                    value="{{ (new \Carbon\Carbon('first day of this month'))->addMonth(-3)->format('Y-m-d')}}"
		                    data-input-format="%Y-%m-%d">
		        </div>
		    </div>
			<div class="row">
		        <div class="cell-1">
				Fin
				</div>
		        <div class="cell-2">
		            <input type="text"
		                    data-role="calendarpicker" 
		                    name="end_date"
		                    value="{{ (new \Carbon\Carbon('last day of this month'))->addMonth(-1)->format('Y-m-d')}}"
		                    data-input-format="%Y-%m-%d">
		        </div>
		    </div>

			<div class="row">
		        <div class="cell-1">	    
				    <button type="submit" class="button primary drop-shadow">Créer</button>
				</div>
			</div>

			<div class="row">
		        <div class="cell-3">
		        	<b>
				</div>
			</div>

			<div class="row">
		        <div class="cell-3">	    
					<b>Exportations de données</b>
				</div>
			</div>

			<div class="row">
		        <div class="cell-3">
		        	<ul>
		        		<li>
							<a href="/export/domains" target="_blank">Exportation des domaines</a>
						</li>
						<li>
							<a href="/export/measures" target="_blank">Exportation des mesures de sécurité</a>
						</li>
						<li>
							<a href="/export/controls" target="_blank">Exportation des contrôles</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</form>
@endsection