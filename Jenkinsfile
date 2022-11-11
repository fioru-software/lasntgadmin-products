void setBuildStatus(String message, String state, String repo ) {
  step([
      $class: "GitHubCommitStatusSetter",
      reposSource: [$class: "ManuallyEnteredRepositorySource", url: "https://github.com/$repo"],
      contextSource: [$class: "ManuallyEnteredCommitContextSource", context: "ci/jenkins/build-status"],
      errorHandlers: [[$class: "ChangingBuildStatusErrorHandler", result: "UNSTABLE"]],
      statusResultSource: [ $class: "ConditionalStatusResultSource", results: [[$class: "AnyBuildResult", message: message, state: state]] ]
  ]);
}

pipeline {

    parameters {
        string(name: 'WP_VERSION', defaultValue: 'latest', description: 'WordPress version')
        string(name: 'WP_LOCALE', defaultValue: 'en_GB', description: 'WordPress locale')
        gitParameter(name: "BRANCH_NAME", type: "PT_BRANCH", defaultValue: "master", branchFilter: "origin/.*")
    }

    agent {
        kubernetes {
            inheritFrom 'jenkins-agent'
            yamlFile 'KubernetesPod.yaml'
        }
    }

    environment {
        PROJECT_NAME = "lasntgadmin-plugin_template"
        REPO_NAME = "fioru-software/$PROJECT_NAME"
        GITHUB_API_URL = "https://api.github.com/repos/$REPO_NAME"
        GITHUB_TOKEN = credentials('jenkins-github-personal-access-token')
        GCLOUD_KEYFILE = credentials('jenkins-gcloud-keyfile')
        USER_ID = 33
        DB_HOST = 'db'
        CONTAINER_NAME = "${PROJECT_NAME}_wordpress_1"
        SITE_URL = 'localhost:8080'
        SITE_TITLE = 'WordPress'
        WP_PLUGINS = 'woocommerce'
        WP_THEME = 'storefront'
        WP_PLUGIN = "example-plugin"
        ADMIN_USERNAME = 'admin'
        ADMIN_PASSWORD = 'secret'
        ADMIN_EMAIL = 'wordpress@example.com'
    }

    stages {

        stage('Build') {

            steps {
                setBuildStatus("Pending", "PENDING", env.REPO_NAME)
                container('docker-compose') {
                    script {
                        sh "touch .env"
                        sh "docker-compose build --build-arg USER_ID=$USER_ID --pull --no-cache wordpress"
                        sh "docker-compose up -d db"
                        sh "docker run --rm -d -e DB_HOST=$DB_HOST -e SITE_URL=$SITE_URL -e SITE_TITLE=$SITE_TITLE -e WP_PLUGINS='$WP_PLUGINS' -e WP_THEME=$WP_THEME -e WP_PLUGIN=$WP_PLUGIN -e ADMIN_USERNAME=$ADMIN_USERNAME -e ADMIN_PASSWORD=$ADMIN_PASSWORD -e ADMIN_EMAIL=$ADMIN_EMAIL --name $CONTAINER_NAME --network ${PROJECT_NAME}_default ${PROJECT_NAME}_wordpress sleep infinity" 
                        sh "docker cp . $CONTAINER_NAME:/usr/local/src/"
                        sh "docker exec -t $CONTAINER_NAME /usr/local/src/scripts/install.sh"
                        sh "docker exec -t $CONTAINER_NAME /usr/local/src/scripts/plugins.sh"
                        sh "docker exec -t -u www-data:www-data -w /var/www/html/wp-content/plugins/$WP_PLUGIN $CONTAINER_NAME composer install"
                        sh "docker exec -t -u www-data:www-data -w /var/www/html/wp-content/plugins/$WP_PLUGIN $CONTAINER_NAME composer all"
                    }
                }
            }
        }
    }
    post {
        success {
            setBuildStatus("Success", "SUCCESS", env.REPO_NAME)
        }
        failure {
            setBuildStatus("Failure", "FAILURE", env.REPO_NAME)
        }
        always {
            container('docker-compose') {
                script {
                    sh "docker stop $CONTAINER_NAME"
                    sh 'docker-compose down -v --remove-orphans'
                }
            }
        }
    }
}
