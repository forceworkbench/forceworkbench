<?php
require_once "context/AbstractSoapConnectionProvider.php";
require_once "soapclient/SforcePartnerClient.php";

class PartnerConnectionProvider extends AbstractSoapConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        $connection =  new SforcePartnerClient();

        $connection->createConnection($this->buildWsdlPath($connConfig));
        $connection->setEndpoint($this->buildEndpoint($connConfig));
        $connection->setSessionHeader($connConfig->getSessionId());
        $connection->setCallOptions(new CallOptions($connConfig->getClientId(), getConfig('callOptions_defaultNamespace')));

        if (getConfig('assignmentRuleHeader_assignmentRuleId') || getConfig('assignmentRuleHeader_useDefaultRule')) {
            $connection->setAssignmentRuleHeader(
                new AssignmentRuleHeader(
                    getConfig('assignmentRuleHeader_assignmentRuleId'),
                    getConfig('assignmentRuleHeader_useDefaultRule')
                )
            );
        }

        if (getConfig('mruHeader_updateMru')) {
            $connection->setMruHeader(new MruHeader(getConfig('mruHeader_updateMru')));
        }

        if (getConfig('queryOptions_batchSize')) {
            $connection->setQueryOptions(new QueryOptions(getConfig('queryOptions_batchSize')));
        }

        if (getConfig('emailHeader_triggerAutoResponseEmail') ||
            getConfig('emailHeader_triggerOtherEmail') ||
            getConfig('emailHeader_triggertriggerUserEmail')) {

            $connection->setEmailHeader(new EmailHeader(
                    getConfig('emailHeader_triggerAutoResponseEmail'),
                    getConfig('emailHeader_triggerOtherEmail'),
                    getConfig('emailHeader_triggertriggerUserEmail')
                )
            );
        }

        if (getConfig('UserTerritoryDeleteHeader_transferToUserId')) {
            $connection->setUserTerritoryDeleteHeader(
                new UserTerritoryDeleteHeader(getConfig('UserTerritoryDeleteHeader_transferToUserId')));
        }

        if (getConfig('allowFieldTruncationHeader_allowFieldTruncation')) {
            $connection->setAllowFieldTruncationHeader(
                new AllowFieldTruncationHeader(getConfig('allowFieldTruncationHeader_allowFieldTruncation')));
        }

        if (getConfig('allOrNoneHeader_allOrNone')) {
            $connection->setAllOrNoneHeader(
			    new AllOrNoneHeader(getConfig('allOrNoneHeader_allOrNone')));
        }

        if (getConfig('disableFeedTrackingHeader_disableFeedTracking')) {
            $connection->setDisableFeedTrackingHeader(
			    new DisableFeedTrackingHeader(getConfig('disableFeedTrackingHeader_disableFeedTracking')));
        }

        if (getConfig('localOptions_language')) {
            $connection->setLocaleOptions(
			    new LocaleOptions(getConfig('localOptions_language')));
        }

        if (getConfig('packageVersionHeader_include') &&
            getConfig('packageVersion_namespace') &&
            getConfig('packageVersion_majorNumber') &&
            getConfig('packageVersion_minorNumber')) {
            $connection->setPackageVersionHeader(
                getConfig("packageVersion_namespace"),
                getConfig("packageVersion_majorNumber"),
                getConfig("packageVersion_minorNumber")
		    );
        }

        return $connection;
    }

    function getWsdlType() {
        return "partner";
    }

    function getEndpointType() {
        return "Soap/u";
    }
}

?>
