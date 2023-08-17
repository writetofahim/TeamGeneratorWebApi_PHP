<?php
namespace App\Enums;

enum PlayerSkill:string{
    case defense = 'defense';
    case attack = 'attack';
    case speed = 'speed';
    case strength = 'strength';
    case stamina = 'stamina';
}