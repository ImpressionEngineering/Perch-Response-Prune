<?php
namespace impeng\responseprune;

class Purge {

    private $deletedIds = array();
    private $deletedDates = array();
    private $messageIntro;
    private $messageList = "";
    private $message;
    private $lastComma;
    private $fullMessageList = "";
    private $summary = "";

    public function deleteResponse($Response) {
        // add this ID to list
        array_push($this->deletedIds,$Response->responseID());
        array_push($this->deletedDates , strtotime($Response->responseCreated()) );
        $Response->delete();
    }

    public function getAdminMessage() {
        $API  = new \PerchAPI(1.0, 'impeng_responseprune');
        $Lang = $API->get('Lang');
        if (count($this->deletedIds) != 0) {
            //make sure message is empty
            $this->message = "";
            $this->messageList = "";
            $this->messageIntro = "";
            // Add summary to start of result message
            $this->messageIntro = $Lang->get('Deleted %s responses with IDs: ', count($this->deletedIds));
            // build a list of deleted Ids
            $this->messageList .= join(', ', $this->deletedIds);
            // concat intro and list
            $this->message = $this->messageIntro.$this->messageList;

            // Result message is restricted to 256 characters, if longer truncate and add ellipsis.
            if (strlen($this->message) > 255) {
                $this->message = substr($this->message,0,250);
                $this->lastComma = strrpos($this->message, ",");
                $this->message = substr($this->message,0,$this->lastComma)." ... ";
            }
        } else {
            //nothing deleted
            $this->message = $Lang->get('Nothing deleted.');
        }
        return $this->message;
    }

    public function getFullList() {  
        $this->fullMessageList = "";
        $this->fullMessageList =  implode(', ', $this->deletedIds);
        return $this->fullMessageList;   
    }

    public function getDeletedIdArray() {   
        return $this->deletedIds;   
    }

    public function getDeletedDatesArray() {   
        return $this->deletedDates;   
    }

    private function compare($item1, $item2){
        if ($item1 > $item2)
           return 1;
        else if ($item1 < $item2)
           return -1;
        else
           return 0;
     }

     public function getDeletedCount() {
        $deletedCount = count($this->deletedIds);
        return $deletedCount;   
    }

     public function getFistDate() {

        if (count($this->deletedIds) != 0) {
            usort($this->deletedDates, array( $this, 'compare' ));
            $firstDate = $this->deletedDates[0];
            $firstDateText = date('j M Y', $firstDate);
            return $firstDateText;   
        }
    }

    public function getLastDate() {

        if (count($this->deletedIds) != 0) {
            usort($this->deletedDates, array( $this, 'compare' ));
            $lastDate = $this->deletedDates[count($this->deletedDates)-1];
            $lastDateText = date('j M Y', $lastDate);
            return $lastDateText;   
        }
    }

    public function getTextSummary() {

        if (count($this->deletedIds) != 0) {
            usort($this->deletedDates, array( $this, 'compare' ));
            $firstDate = date('j M Y', $this->deletedDates[0]);
            $lastDate = date('j M Y', $this->deletedDates[count($this->deletedDates)-1]);
            $this->summary = "The purge process deleted " . 
                count($this->deletedIds) . 
                " form responses which were received in the period from " . 
                $firstDate . 
                " to " . 
                $lastDate . ".";
            return $this->summary;    
        }
        else {
            return "nothing deleted.";
        }
    }
}
