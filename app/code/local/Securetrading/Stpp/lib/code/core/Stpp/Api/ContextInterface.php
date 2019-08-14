<?php

interface Stpp_Api_ContextInterface {
   function setRequests(array $requests);
   function &getRequests();
   function getRequest($index);
   function setResponses(array $responses);
   function &getResponses();
}