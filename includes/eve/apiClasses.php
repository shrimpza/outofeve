<?php
    
    class eveCharacterFaction {
        var $factionID = 0;
        var $factionName = '';
        var $enlisted = 0;
        var $currentRank = 0;
        var $highestRank = 0;
        var $killsYesterday = 0;
        var $killsLastWeek = 0;
        var $killsTotal = 0;
        var $victoryPointsYesterday = 0;
        var $victoryPointsLastWeek = 0;
        var $victoryPointsTotal = 0;

        function eveCharacterFaction($acc, $db, $faction) {
            $this->factionID = (int)$faction->trainingTypeID;
            $this->factionName = (string)$faction->factionName;
            $this->enlisted = strtotime((string)$faction->enlisted) + $acc->timeOffset;
            $this->currentRank = (int)$faction->currentRank;
            $this->highestRank = (int)$faction->highestRank;
            $this->killsYesterday = (int)$faction->killsYesterday;
            $this->killsLastWeek = (int)$faction->killsLastWeek;
            $this->killsTotal = (int)$faction->killsTotal;
            $this->victoryPointsYesterday = (int)$faction->victoryPointsYesterday;
            $this->victoryPointsLastWeek = (int)$faction->victoryPointsLastWeek;
            $this->victoryPointsTotal = (int)$faction->victoryPointsTotal;
        }
    }
?>