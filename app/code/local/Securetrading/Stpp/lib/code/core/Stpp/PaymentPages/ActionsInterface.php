<?php

interface Stpp_PaymentPages_ActionsInterface extends Stpp_Actions_BaseInterface {
    function prepareResponse(Stpp_Data_Response $response);
    function checkIsNotificationProcessed($notificationReference);
    function validateNotification(Stpp_Data_Response $response);
    function saveNotificationReference($notificationReference);
}