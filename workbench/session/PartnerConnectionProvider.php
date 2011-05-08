<?php
require_once "session/AbstractConnectionProvider.php";
require_once 'soapclient/SforcePartnerClient.php';

class PartnerConnectionProvider extends AbstractConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        $partnerConnection =  new SforcePartnerClient();

        $partnerConnection->createConnection($this->buildWsdlPath($connConfig));
        $partnerConnection->setEndpoint($this->buildEndpoint($connConfig));
        $partnerConnection->setSessionHeader($connConfig->getSessionId());

        if (isset($_SESSION['tempClientId'])) {
            $partnerConnection->setCallOptions(new CallOptions($_SESSION['tempClientId'], getConfig('callOptions_defaultNamespace')));
        } else if (getConfig('callOptions_client') || getConfig('callOptions_defaultNamespace')) {
            $partnerConnection->setCallOptions(new CallOptions(getConfig('callOptions_client'), getConfig('callOptions_defaultNamespace')));
        }

        if (getConfig('assignmentRuleHeader_assignmentRuleId') || getConfig('assignmentRuleHeader_useDefaultRule')) {
            $partnerConnection->setAssignmentRuleHeader(
                new AssignmentRuleHeader(
                    getConfig('assignmentRuleHeader_assignmentRuleId'),
                    getConfig('assignmentRuleHeader_useDefaultRule')
                )
            );
        }

        if (getConfig('mruHeader_updateMru')) {
            $partnerConnection->setMruHeader(new MruHeader(getConfig('mruHeader_updateMru')));
        }

        if (getConfig('queryOptions_batchSize')) {
            $partnerConnection->setQueryOptions(new QueryOptions(getConfig('queryOptions_batchSize')));
        }

        if (getConfig('emailHeader_triggerAutoResponseEmail') ||
            getConfig('emailHeader_triggerOtherEmail') ||
            getConfig('emailHeader_triggertriggerUserEmail')) {

            $partnerConnection->setEmailHeader(new EmailHeader(
                    getConfig('emailHeader_triggerAutoResponseEmail'),
                    getConfig('emailHeader_triggerOtherEmail'),
                    getConfig('emailHeader_triggertriggerUserEmail')
                )
            );
        }

        if (getConfig('UserTerritoryDeleteHeader_transferToUserId')) {
            $partnerConnection->setUserTerritoryDeleteHeader(
                new UserTerritoryDeleteHeader(getConfig('UserTerritoryDeleteHeader_transferToUserId')));
        }

        if (getConfig('allowFieldTruncationHeader_allowFieldTruncation')) {
            $partnerConnection->setAllowFieldTruncationHeader(
                new AllowFieldTruncationHeader(getConfig('allowFieldTruncationHeader_allowFieldTruncation')));
        }

        if (getConfig('allOrNoneHeader_allOrNone')) {
            $partnerConnection->setAllOrNoneHeader(
			    new AllOrNoneHeader(getConfig('allOrNoneHeader_allOrNone')));
        }

        if (getConfig('disableFeedTrackingHeader_disableFeedTracking')) {
            $partnerConnection->setDisableFeedTrackingHeader(
			    new DisableFeedTrackingHeader(getConfig('disableFeedTrackingHeader_disableFeedTracking')));
        }

        if (getConfig('localOptions_language')) {
            $partnerConnection->setLocaleOptions(
			    new LocaleOptions(getConfig('localOptions_language')));
        }

        if (getConfig('packageVersionHeader_include') &&
            getConfig('packageVersion_namespace') &&
            getConfig('packageVersion_majorNumber') &&
            getConfig('packageVersion_minorNumber')) {
            $partnerConnection->setPackageVersionHeader(
                getConfig("packageVersion_namespace"),
                getConfig("packageVersion_majorNumber"),
                getConfig("packageVersion_minorNumber")
		    );
        }

        return $partnerConnection;
    }

    function getWsdlType() {
        return "partner";
    }

    function getEndpointType() {
        return "Soap/u";
    }
}

?>
