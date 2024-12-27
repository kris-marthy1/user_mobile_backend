<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class ServiceWindowController extends Controller
{

    public function getTableColumns(Request $request)
{
    $tableName = $request->input('table_name');

    if (!Schema::hasTable($tableName)) {
        return response()->json(['error' => 'Table not found'], 404);
    }

    $columns = Schema::getColumnListing($tableName);
    $filteredColumns = array_filter($columns, function ($column) {
        return !in_array($column, [ 'created_at', 'updated_at']);
    });

    return response()->json(array_values($filteredColumns)); // Return only the column names
}

public function exitQueue(Request $request)
{
    $sessionId = $request->input('sessionId');
    $window_id = $request->input('window_id');
    $queue_id = $request->input('queue_id');

    // Fetch window_name based on window_id
    $window = DB::connection('clientone')->table('window')
                ->where('window_id', $window_id)
                ->first();

    // Check if window data exists
    if (!$window) {
        return response()->json(['error' => 'Window not found'], 404);
    }

    $window_name = $window->window_name; // Access window_name from the result


    // Check if window_name is valid
    if (!$window_name) {
        return response()->json(['error' => 'Invalid window name'], 400);
    }


    // Delete the session from user_sessions table
    DB::connection('clientone')->table('user_sessions')
    ->where('id', $sessionId)
    ->where('window_id', $window_id)
    ->where('queue_id', $queue_id)
    ->delete();

    // Delete from the dynamic table using window_name
    $deletedRows = DB::connection('clientone')->table($window_name)
        ->where('queue_id', $queue_id)
        ->delete();

    // Return success message
    return response()->json(['message' => 'Exited queue successfully']);
}



public function joinQueue(Request $request) {
    $tableName = $request->input('table_name');

    // Check if the table exists
    if (!Schema::hasTable($tableName)) {
        return response()->json(['error' => 'Table not found'], 404);
    }

    $data = $request->except('table_name');
    $data['created_at'] = now();
    $data['updated_at'] = now();

    // Insert data into the specified table and get the ID of the inserted record
    $insertedId = DB::table($tableName)->insertGetId($data);

    $window = DB::table('window')->where('window_name', $tableName)->first();

    if (!$window) {
        return response()->json(['error' => 'Window not found for the specified table'], 404);
    }

    $windowId = $window->window_id;

    if ($insertedId) {
        // Create a custom session to identify that the user is in the queue
        $sessionData = [
            'ip_address' => $request->ip(),
            'window_id' => $windowId,
            'queue_id' => $insertedId, // Use the ID of the inserted record as school_id
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Insert session data into the custom user_sessions table
        DB::connection('clientone')->table('user_sessions')->insert($sessionData);

        // Store session details in the response
        return response()->json([
            'message' => 'Joined queue successfully',
            'session_data' => $sessionData,
        ]);
    }

    return response()->json(['error' => 'Failed to join the queue'], 500);
}

public function fetchIP(Request $request) {
        return response()->json(['ip_address' => $request->ip()]);
}

public function queueStatus(Request $request) {
    try {
        $tableName = $request->input('table_name');

        // Validate input
        if (!$tableName) {
            return response()->json(['error' => 'Table name and school ID are required'], 400);
        }

        $window = DB::connection('clientone')->table('window')
            ->where('window_name', $tableName)
            ->first();

        // Check if window data exists
        if (!$window) {
            return response()->json(['error' => 'Window not found'], 404);
        }

        $window_id = $window->window_id;

        // Check session
        $session = DB::connection('clientone')->table('user_sessions')
            ->where('ip_address', $request->ip())
            ->where('window_id', $window_id)
            ->first();

        $user_in_queue = DB::connection('clientone')->table($tableName)
        ->where('queue_id', $session->queue_id)
        ->first();

        if ($session) {

            return response()->json([
                'in_queue' => true,
                'window_id' => $session->window_id,
                'window_name' => $window->window_name,
                'id' => $session->id,
                'queue_id' => $session->queue_id, // Changed from school_id
                'session_created_at' => $session->created_at,
                'ip_address' => $session->ip_address,
            ]);
        }



        return response()->json(['error' => 'No active queue session'], 404);
    } catch (Exception $e) {

        return response()->json([
            'error' => 'Internal server error',
            'message' => $e->getMessage()
        ], 500);
    }
}



// public function queueSession(Request $request) {
//     try {
//         $tableName = $request->input('table_name');
        
//         $window = DB::connection('clientone')->table('window')
//             ->where('window_name', $tableName)
//             ->first();

//         // Check if window data exists
//         if (!$window) {
//             return response()->json(['error' => 'Window not found'], 404);
//         }

//         $window_id = $window->window_id;

//         $session = DB::connection('clientone')->table('user_sessions')
//             ->where('ip_address', $request->ip())
//             ->where('window_id', $window_id)
//             ->first();

//         if ($session) {
//             return response()->json([
//                 'in_queue' => true,
//                 'window_id' => $session->window_id,
//                 'window_name' => $window->window_name,
//                 'school_id' => $session->school_id,
//                 'id' => $session->id,
//                 'session_created_at' => $session->created_at,
//             ]);
//         }

//         return response()->json(['error' => 'No active queue session'], 404);
//     } catch (Exception $e) {
        
//         return response()->json(['error' => 'Internal server error'], 500);
//     }
// }


    public function getFilteredTables()
{
    // All tables to exclude
    // $excludedTables = [
    //     'account_infos', 'cache', 'cache_locks', 'migrations', 
    //     'model_has_permissions', 'model_has_roles', 'password_reset_tokens', 
    //     'permissions', 'history', 'roles', 'role_has_permissions', 
    //     'sessions', 'user_infos', 'window', 'user_sessions'
    // ];

    // Fetch all table names and exclude the specified ones
    // $tables = collect(DB::select('SHOW TABLES'))
    //     ->map(function ($table) {
    //         return reset($table); // Get the first column value (table name)
    //     })
    //     ->reject(function ($tableName) use ($excludedTables) {
    //         return in_array($tableName, $excludedTables);
    //     })
    //     ->values();

    $tables = DB::connection('clientone')->table('window')
    ->get();

    return response()->json($tables);
}

public function getTableData(Request $request)
{
    $tableName = $request->input('table_name');

    if (!Schema::hasTable($tableName)) {
        return response()->json(['error' => 'Table not found'], 404);
    }

    $data = DB::table($tableName)->get();
    return response()->json($data);
}

// public function checkUserInQueue(Request $request)
// {
//     $user = DB::table($request->table_name)
//               ->where('school_id', $request->school_id)
//               ->first();

//     return response()->json(['isInQueue' => !is_null($user)]);
// }

// public function joinQueue(Request $request)
// {
//     $tableName = $request->input('table_name');

//     // Check if the table exists
//     if (!Schema::hasTable($tableName)) {
//         return response()->json(['error' => 'Table not found'], 404);
//     }

//     // Add timestamps to the request data
//     $data = $request->except('table_name');
//     $data['created_at'] = now();
//     $data['updated_at'] = now();

//     // Insert the data into the table
//     DB::table($tableName)->insert($data);

//     return response()->json(['message' => 'Joined queue successfully']);
// }


public function duration(Request $request)
{
    $tableName = $request->input('table_name');

    $window = DB::connection('clientone')->table('window')
                ->where('window_name', $tableName)
                ->first();

    if (!$window) {
        return response()->json(['error' => 'Window not found'], 404);
    }

    $window_id = $window->window_id;

    $history = DB::connection('clientone')->table('history')
        ->where('window_id', $window_id)
        ->get();

    $data = [];
    $totalDuration = 0;
    $currentPosition = 1;

    foreach ($history as $item) {
        $arrivalTime = Carbon::parse($item->arrived_at);
        $completionTime = Carbon::parse($item->created_at);
        $durationInMinutes = $completionTime->diffInMinutes($arrivalTime);
        $totalDuration += $durationInMinutes;
    }

    if(count($history)){
        $averageDuration = $totalDuration / count($history);
        return response()->json(['estimated_duration' => round($averageDuration)*(-1)]);

    }else{
        $averageDuration = null;
        return response()->json(['estimated_duration' => NULL]);

    }


}

}// closing 
