
# Containerized LAMP Application on Amazon ECS Fargate (with Secrets Manager)

This project demonstrates how to build and deploy a containerized LAMP (Linux, Apache, MySQL, PHP) application on **Amazon ECS using Fargate**, securely retrieving database credentials from **AWS Secrets Manager**.

---

## Stack

- **PHP 8.x** with Apache
- **MySQL** on Amazon RDS
- **Docker** (built using Colima with x86_64 support)
- **Amazon ECR** – for storing Docker images
- **Amazon ECS (Fargate)** – for container orchestration
- **AWS Secrets Manager** – for securely storing DB credentials
- **AWS CLI & Console** – for infrastructure interaction
- **Monitoring**: for CloudWatch Logs

---

## Project Structure

```
lamp-app/
├── Dockerfile
├── README.md
├── lamp-task-def.json
├── php-app
│   ├── config.php
│   ├── index.php
│   └── styles.css
└── screenshots

```
---

## Architecture

![ECS Lamp Stack App Architecture](screenshots/ecs-lamp-app-arch.png)

---
## Live App

**http://108.130.104.213/**

![ECS Lamp Stack App](screenshots/lamp-stack-app-ecs-home-page.png)

---
## Setup Instructions

### Build and Push Docker Image

```bash
docker build -t lamp-app .

# Tag and push to ECR
docker tag lamp-app:latest <account>.dkr.ecr.<region>.amazonaws.com/lamp-app:latest
docker push <account>.dkr.ecr.<region>.amazonaws.com/lamp-app:latest
```

---

## Use AWS Secrets Manager

### Step 1: Create a Secret

```bash
aws secretsmanager create-secret   --name lamp-db-credentials   --description "RDS credentials for LAMP app"   --secret-string '{
    "DB_ENDPOINT": "your-rds-endpoint:3306",
    "DB_USERNAME": "admin",
    "DB_PASSWORD": "yourpassword",
    "DB_NAME": "lampdb"
  }'
```

### Step 2: Grant Permission to ECS Task Role

Ensure the `ecsTaskExecutionRole` has permission to access Secrets Manager:

```json
{
  "Effect": "Allow",
  "Action": "secretsmanager:GetSecretValue",
  "Resource": "arn:aws:secretsmanager:<region>:<account>:secret:lamp-db-credentials*"
}
```

Attach this to the role via IAM Console or CLI.

---

## ECS Task Definition via Console

1. Open ECS Console → Task Definitions → Create New Revision
2. Delete any plaintext DB environment variables
3. Scroll to **Secrets → Environment Variables**
4. Add the following keys using the secret `lamp-db-credentials`:

   | Name         | ValueFrom Key in Secret |
   |--------------|--------------------------|
   | DB_ENDPOINT  | DB_ENDPOINT              |
   | DB_USERNAME  | DB_USERNAME              |
   | DB_PASSWORD  | DB_PASSWORD              |
   | DB_NAME      | DB_NAME                  |

5. Save and create the revision

---

##  Deploy ECS Service via Console

1. ECS Console → Clusters → `lamp-app-cluster` → Create Service
2. Launch type: **Fargate**
3. Task definition: `lamp-app-task` (latest revision)
4. Subnet: Select **public subnet**
5. Security group: allow inbound `TCP 80`
6. Assign public IP: `ENABLED`

Click **Create Service**

---

## 🌐 Access the App

Get the task IP address via CLI:

```bash
aws ecs list-tasks --cluster lamp-app-cluster
aws ecs describe-tasks --cluster lamp-app-cluster --tasks <task-id>
aws ec2 describe-network-interfaces --network-interface-ids <eni-id>   --query "NetworkInterfaces[0].Association.PublicIp" --output text
```

Then visit: `http://<public-ip>`

---

## CloudWatch Logs (Monitoring)

This project integrates Amazon CloudWatch Logs for real-time visibility.

### Setup Steps

1. **Create a CloudWatch Log Group**
```
aws logs create-log-group --log-group-name /ecs/lamp-app

```

2. **Update ECS Execution Role**
```
{
  "Effect": "Allow",
  "Action": ["logs:CreateLogStream", "logs:PutLogEvents"],
  "Resource": "*"
}

```

3. **Update Task Definition Logging**
```
"logConfiguration": {
  "logDriver": "awslogs",
  "options": {
    "awslogs-group": "/ecs/lamp-app",
    "awslogs-region": "eu-west-1",
    "awslogs-stream-prefix": "lamp"
  }
}

```

4. **Redeploy Task & Service**
```
aws ecs register-task-definition --cli-input-json file://lamp-task-def-logs.json

aws ecs update-service --cluster lamp-app-cluster --service lamp-app-service --task-definition lamp-app-task

```

5. **View Logs**

Go to `AWS Console → CloudWatch Logs → /ecs/lamp-app`

---

## Summary

- Your PHP app reads DB credentials from `getenv()`
- ECS injects secrets securely as environment variables
- No `.env` file is needed in the container

---

## CI/CD Pipeline (GitHub Actions)

This project uses **GitHub Actions** to automatically update the ECS service whenever the task definition (`lamp-task-def.json`) is changed.

### CI/CD Workflow

- Triggered on push to `lamp-task-def.json`
- Registers a new task definition with ECS
- Updates the ECS service to use the new task revision

### Required GitHub Secrets

| Secret Name             | Description                        |
|-------------------------|------------------------------------|
| `AWS_ACCESS_KEY_ID`     | IAM user's access key              |
| `AWS_SECRET_ACCESS_KEY` | IAM user's secret access key       |
| `AWS_REGION`            | AWS Region (e.g., `eu-west-1`)     |

### 🛠 Workflow File

`.github/workflows/ecs-deploy.yml`

```yaml
name: Deploy ECS Task

on:
  push:
    paths:
      - 'lamp-task-def.json'

jobs:
  deploy:
    name: Register new task definition & update ECS service
    runs-on: ubuntu-latest

    steps:
      - name: Checkout repo
        uses: actions/checkout@v3

      - name: Configure AWS credentials
        uses: aws-actions/configure-aws-credentials@v4
        with:
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          aws-region: ${{ secrets.AWS_REGION }}

      - name: Register ECS Task Definition
        id: register
        run: |
          TASK_DEF_ARN=$(aws ecs register-task-definition             --cli-input-json file://lamp-task-def.json             --query "taskDefinition.taskDefinitionArn"             --output text)
          echo "TASK_DEF_ARN=$TASK_DEF_ARN" >> "$GITHUB_ENV"

      - name: Update ECS Service
        run: |
          aws ecs update-service             --cluster lamp-app-cluster             --service lamp-app-service             --task-definition "$TASK_DEF_ARN"

```

---
## Author

**Humaidu Ali Mohammed**  

Containerized LAMP App • ECS Fargate Deployment Lab with AWS Secrets Manager

