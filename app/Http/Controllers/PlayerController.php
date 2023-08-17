<?php

// /////////////////////////////////////////////////////////////////////////////
// PLEASE DO NOT RENAME OR REMOVE ANY OF THE CODE BELOW. 
// YOU CAN ADD YOUR CODE TO THIS FILE TO EXTEND THE FEATURES TO USE THEM IN YOUR WORK.
// /////////////////////////////////////////////////////////////////////////////

namespace App\Http\Controllers;
use App\Models\Player;

use Illuminate\Http\Request;
use App\Enums\PlayerPosition;

class PlayerController extends Controller
{
    protected function playersInRequiredFormat ($player){
                        $playerData = [
                            'id' => $player->id,
                            'name' => $player->name,
                            'position' => $player->position->value, 
                            'playerSkills' => [],
                        ];
                        
                        foreach ($player->skills as $skill) {
                            $skillData = [
                                'id'=>$skill->id,
                                'skill' => $skill->skill->value, 
                                'value' => $skill->value,
                                'playerId'=>$skill->player_id,
                            ];
                            
                            $playerData['playerSkills'][] = $skillData;
                        }
                    return $playerData;
    }

    public function index()
    {
        $players = Player::all();
        $extractedData = [];

        foreach ($players as $player){
            $extractedData[] = $this->playersInRequiredFormat($player);
        }

        return response()->json($extractedData);
    }

    public function show()
    {
        return response("Failed", 500);
    }

    public function store(Request $request)
{
    try {
        
        $position = $request->input('position');
        $validPositions = ["defender", "midfielder", "forward"];
        if (!in_array($position, $validPositions)) {
        return response()->json([
        'message' => "Invalid value for position: {$position}",
        'field' => 'position'
        ], 422);
        }

        $validSkills = ["defense", "attack", "speed", "strength", "stamina"];
        $playerSkills = $request->input('playerSkills');

        if (!is_array($playerSkills) || count($playerSkills) === 0) {
        return response()->json([
        'message' => 'At least one value is required for the player',
        'field' => 'playerSkills'
        ], 422);
        }

        foreach ($playerSkills as $skillData) {
        if (!in_array($skillData['skill'], $validSkills)) {
        return response()->json([
            'message' => 'Invalid value for skill: ' . $skillData['skill'],
            'field' => 'skill'
        ], 422);
        }
        }


        $player = new Player();
        $playerData = $request->only(['name', 'position']);
        $player = Player::create($playerData);

        $skillsData = $request->input('playerSkills');
        if ($skillsData && is_array($skillsData)) {
            foreach ($skillsData as $skillData) {
                $player->skills()->create([
                    'skill' => $skillData['skill'],
                    'value' => $skillData['value']
                ]);
            }
        }
        $player->load('skills');

        return response()->json($this->playersInRequiredFormat($player), 201);
    } catch (\Exception $e) {
        dd($e);
        return response()->json(['error' => 'Failed to create player and skills'], 500);
    }
}


    public function update(Request $request, $id)
    {
        $name = $request->input('name');
        $position = $request->input('position');
        $playerSkills = $request->input('playerSkills');

        try{
            $player = Player::find($id);
            if(!$player){
            return response()->json(['message' => 'Player does not exist'], 404);
            }
            $validPositions = ["defender", "midfielder", "forward"];
            if (!in_array($position, $validPositions)) {
            return response()->json([
            'message' => "Invalid value for position: {$position}",
            'field' => 'position'
            ], 422);
            }

            if (!is_array($playerSkills) || count($playerSkills) === 0) {
                return response()->json([
                'message' => 'At least one skill is required for the player',
                'field' => 'playerSkills'
                ], 422);
                }

            
            
                $validSkills = ["defense", "attack", "speed", "strength", "stamina"];
        
                foreach ($playerSkills as $skillData) {
                if (!in_array($skillData['skill'], $validSkills)) {
                return response()->json([
                    'message' => 'Invalid value for skill: ' . $skillData['skill'],
                    'field' => 'skill'
                ], 422);
                }
                }
            // Update player fields using the 'fill' method
            $player->fill($request->only(['name', 'position']));
            $player->save();

            
            $skillsData = $request->input('playerSkills');
            if (is_array($skillsData)) {
                // Delete existing skills not in the updated skills data
                $existingSkills = array_column($skillsData, 'skill');
                $player->skills()
                    ->whereNotIn('skill', $existingSkills)
                    ->delete();
                    // dd($player->skills()->pluck('skill'));
            
                foreach ($skillsData as $skillData) {
                    $skillName = $skillData['skill'];
                    $skillValue = $skillData['value'];
                    
                    // Update or create the skill
                    $player->skills()->updateOrCreate(
                        ['skill' => $skillName],
                        ['value' => $skillValue]
                    );
                }
            }
            $player->load('skills');
            
            return response()->json($this->playersInRequiredFormat($player), 200);
    

    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to update player and skills'], 500);
    }

    }

    public function destroy($id)
    {
        $player = Player::find($id);

        if(!$player){
            return response()->json(['message' => 'Player does not exist'], 404);
 
        }
        // Delete associated playerSkills
        $player->skills()->delete();
        $player->delete();

    return response()->json(['message' => 'Player deleted successfully'], 200);
    
}
public function __construct()
{
    $this->middleware('token.auth', ['only' => ['destroy']]);
}
}
