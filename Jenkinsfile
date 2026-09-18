pipeline {
    agent any

    environment {
        IMAGE_NAME = "aws-ecommerce"
        IMAGE_TAG  = "${BUILD_NUMBER}"
        K8S_FILE   = "k8s/deployment.yaml"
    }

    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 15, unit: 'MINUTES')
        disableConcurrentBuilds()
    }

    triggers {
        githubPush()
    }

    stages {
        stage('PHP Lint') {
            steps {
                sh '''
                    find . -name "*.php" ! -path "./vendor/*" ! -path "./uploads/*" -exec php -l {} +
                '''
            }
        }

        stage('Build Docker Image') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'Docker', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                    sh """
                        docker build -t \$DOCKER_USER/${IMAGE_NAME}:${IMAGE_TAG} -t \$DOCKER_USER/${IMAGE_NAME}:latest .
                    """
                }
            }
        }

        stage('Push Docker Image') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'Docker', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                    sh """
                        echo "\$DOCKER_PASS" | docker login -u "\$DOCKER_USER" --password-stdin
                        docker push \$DOCKER_USER/${IMAGE_NAME}:${IMAGE_TAG}
                        docker push \$DOCKER_USER/${IMAGE_NAME}:latest
                        docker logout
                    """
                }
            }
        }

        stage('Deploy to Kubernetes') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'Docker', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                    sh """
                        kubectl delete pvc mysql-pvc --ignore-not-found=true || true
                        kubectl delete pv mysql-pv --ignore-not-found=true || true
                        kubectl apply -f ${K8S_FILE}
                        kubectl rollout status deployment/mysql --timeout=120s
                        kubectl set image deployment/ecommerce-app web=\$DOCKER_USER/${IMAGE_NAME}:${IMAGE_TAG}
                        kubectl rollout status deployment/ecommerce-app --timeout=180s
                    """
                }
            }
        }
    }

    post {
        failure {
            sh """
                kubectl describe pods -l app=ecommerce-web || true
                kubectl logs -l app=ecommerce-web --tail=50 || true
                kubectl describe pods -l app=mysql || true
                kubectl logs -l app=mysql --tail=50 || true
                kubectl get pvc || true
                kubectl get svc || true
            """
        }
        always {
            sh 'docker image prune -f || true'
        }
    }
}
