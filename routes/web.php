<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/excel', function () {
    $customers = \App\Models\Ileva\IlevaAssociateVehicle::getVehiclesForFieldControl();
    $filePath = storage_path('app/public/RELATORIO - FIELDCONTROL.xlsx');

    foreach ($customers as $customer) {
        \App\Jobs\UpdateFieldControlCustomerExcelJob::dispatch($customer);
        dd();
    }

    return 'Field Control customers update jobs dispatched successfully.';
});
