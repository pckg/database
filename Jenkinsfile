node {
    stage('Checkout') {
        checkout scm
    }

    stage('Build') {
        echo "My branch is: ${env.BRANCH_NAME}"
    }

    stage('Test') {
        sh "uptime"
    }
}