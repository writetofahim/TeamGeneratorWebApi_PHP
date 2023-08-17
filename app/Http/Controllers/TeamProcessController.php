<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;

class TeamProcessController extends Controller
{
    protected function bestPlayerFinder($selectedPlayers, $mainSkill, $exceptThisSkill){
        
        $selectedPlayers=$selectedPlayers;
        //filtering players with main skill
        if($mainSkill){
            $s=[];
            foreach($selectedPlayers as $player){
                $skills=$player['skills'];
                foreach($skills as $skill){
                    // dd($skill['skill']->value);
                    if($skill['skill']->value==$mainSkill){
                        $s[]=$player;
                        break;
                }

                }
                // dd($player);
            }
            $selectedPlayers=$s;
        }
        // filtering players without main skill
        if($exceptThisSkill){
            $s=[];
            foreach ($selectedPlayers as $player) {
                $hasMainSkill = false;
                $skills=$player['skills'];
                foreach($skills as $skill){
                    if($skill['skill']->value==$exceptThisSkill){
                        $hasMainSkill = true;
                        break; 
                    }
                }
                if (!$hasMainSkill) {
                    $s[] = $player;
                }
            }
            $selectedPlayers= $s;
            // dd($selectedPlayers);
        }
        //sorting players
        for ($i = 0; $i < count($selectedPlayers) - 1; $i++) {
            $maxIndex = $i;
            $maxSkillValue = $this->getMaxSkillValue($selectedPlayers[$i]['skills'], $mainSkill);

            for ($j = $i + 1; $j < count($selectedPlayers); $j++) {
                $currentMaxSkillValue = $this->getMaxSkillValue($selectedPlayers[$j]['skills'], $mainSkill);
                if ($currentMaxSkillValue > $maxSkillValue) {
                    $maxIndex = $j;
                    $maxSkillValue = $currentMaxSkillValue;
                }
            }
    
            if ($maxIndex !== $i) {
                $temp = $selectedPlayers[$i];
                $selectedPlayers[$i] = $selectedPlayers[$maxIndex];
                $selectedPlayers[$maxIndex] = $temp;
            }
        }      

        return $selectedPlayers;
    }

    protected function getMaxSkillValue($playerSkills, $mainSkill) {
        $maxSkillValue = 0;
        
        if($mainSkill){
            foreach($playerSkills as $skill){
                if($skill['skill']->value == $mainSkill){
                    $maxSkillValue = $skill['value'];
                }
            }
        }else{
            foreach ($playerSkills as $skill) {
                if ($skill['value'] > $maxSkillValue) {
                    $maxSkillValue = $skill['value'];
                    // dd($skill['skill']->value);
                }
            }
        }
        return $maxSkillValue;
    }  

    protected function playersInRequiredFormat ($players){
        $extractedData = [];
                    // return response()->json($bestPlayers);
                    foreach ($players as $player) {
                        $playerData = [
                            // 'id' => $player->id,
                            'name' => $player->name,
                            'position' => $player->position->value, // Assuming PlayerPosition enum has a value property
                            'playerSkills' => [],
                        ];
                        
                        foreach ($player->skills as $skill) {
                            $skillData = [
                                'skill' => $skill->skill->value, // Assuming PlayerSkill enum has a value property
                                'value' => $skill->value,
                            ];
                            
                            $playerData['playerSkills'][] = $skillData;
                        }
                        
                        $extractedData[] = $playerData;
                    }
                    return $extractedData;
    }

    public function process(Request $request)
    {

        $inputArray = $request->all();
        for($i =0; $i<count($inputArray); $i++){
            $positionI=$inputArray[$i]['position'];
            $mainSkillI=$inputArray[$i]['mainSkill'];
            for($j =$i+1; $j<count($inputArray); $j++){
                $positionJ=$inputArray[$j]['position'];
                $mainSkillJ=$inputArray[$j]['mainSkill'];

                if($positionI==$positionJ && $mainSkillI==$mainSkillJ ){
    
                    return response()->json([
                        'message' => "Cannot send a request with multiple requirements for {$positionI} with the highest {$mainSkillI}",
                        'field' => 'position and skill'
                        ], 422);
                }
            }
            
        }
        foreach ($inputArray as $playerDetails) {
            $position = $playerDetails['position'];
            $mainSkill = $playerDetails['mainSkill'];
            $numberOfPlayers = $playerDetails['numberOfPlayers'];
            
            $validPositions = ["defender", "midfielder", "forward"];
            if (!in_array($position, $validPositions)) {
            return response()->json([
            'message' => "Invalid value for position: {$position}",
            'field' => 'position'
            ], 422);
            }
    
            $validSkills = ["defense", "attack", "speed", "strength", "stamina"];
            if (!in_array($mainSkill, $validSkills)){
                return response()->json([
                    'message' => "Invalid value for skill:  {$mainSkill}",
                    'field' => 'skill'
                ], 422);
            }
    
            
            if (!is_numeric($numberOfPlayers) || $numberOfPlayers <= 0) {
                return response()->json([
                    'message' => "Invalid numberOfPlayers: {$numberOfPlayers}",
                    'field' => 'numberOfPlayers'
                ], 422);
            }
            
        }
        foreach ($inputArray as $playerDetails) {
            $position = $playerDetails['position'];
            // $mainSkill = $playerDetails['mainSkill'];
            $numberOfPlayers = $playerDetails['numberOfPlayers'];

            $players = Player::where('position', $position)->get();
            // dd($players);

            if ($players->count() < $numberOfPlayers) {
                return response()->json([
                    'message' => "Insufficient numberOfPlayers for position: {$numberOfPlayers}",
                    'field' => "position",
                    
                ], 422);
            }           
        }

        $selectedPlayers = [];
        $bestPlayersCollector=[];
        foreach ($inputArray as $playerDetails) {
            $position = $playerDetails['position'];
            $mainSkill = $playerDetails['mainSkill'];
            $numberOfPlayers = $playerDetails['numberOfPlayers'];
            $flag = false;
            // players with skill's order is desc order
            $players = Player::where('position', $position)
                ->whereHas('playerSkills', function ($query) use ($mainSkill) {
                    $query->where('skill', $mainSkill);
                })
                ->with(['playerSkills'])
                ->get();
                
            // Hide the "skills" attribute for each player
            $players->each(function ($player) {
                $player->makeHidden(['skills']);
            });
            // players without specific skill with skill's order is desc order
            $playersWithOtherSkill = Player::where('position', $position)
            ->with(['playerSkills' => function ($query) {
                $query->orderBy('value', 'desc');
            }])
            ->get();
            // Hide the "skills" attribute for each player
            $playersWithOtherSkill->each(function ($player) {
                $player->makeHidden(['skills']);
            });
                    //finding best players with mainSkill
                    $bestPlayers = $this->bestPlayerFinder(
                        $players,
                        $mainSkill,
                        null
                    );
                    //getting format
                    $playersInRequiredFormat= $this->playersInRequiredFormat($bestPlayers);

                    if( count($playersInRequiredFormat) < $numberOfPlayers && count($playersInRequiredFormat)!=0){
                        $bestOtherSkillPlayers= $this->bestPlayerFinder($playersWithOtherSkill, null, $mainSkill);
                        array_push($playersInRequiredFormat, ...$this->playersInRequiredFormat($bestOtherSkillPlayers));
                    }
                    
                    if(count($playersInRequiredFormat)==0){
                        $bestPlayersWithAllSkill = $this->bestPlayerFinder(
                            $playersWithOtherSkill,
                            null,
                            null
                        );
                        
                        $playersInRequiredFormat= $this->playersInRequiredFormat($bestPlayersWithAllSkill);
                    }
                    
            $bestPlayers = array_push($bestPlayersCollector, ...array_slice($playersInRequiredFormat, 0, $numberOfPlayers));
            
        }
        return response()->json($bestPlayersCollector);
    }    
}
