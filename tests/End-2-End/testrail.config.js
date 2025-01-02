module.exports = {
    base_url: process.env.TESTRAIL_DOMAIN,
    user: process.env.TESTRAIL_USERNAME,
    pass: process.env.TESTRAIL_PASSWORD,
    project_id: process.env.TESTRAIL_PROJECT_ID,
    suite_id: process.env.TESTRAIL_SUITE_ID,
    testRailUpdateInterval: 0,
    updateResultAfterEachCase: true,
    use_existing_run: {
        id: 0,
    },
    create_new_run: {
        include_all: false,
        run_name: process.env.TESTRAIL_RUN_NAME,
        milestone_id: 0
    },
    status: {
        passed: 1,
        failed: 5,
        untested: 3,
        skipped: 6
    }
};
