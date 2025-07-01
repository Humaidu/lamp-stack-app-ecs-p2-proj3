
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

---

## Project Structure

```
lamp-app/
├── Dockerfile
├── index.php
├── config.php
├── styles.css
```

---

## Architecture


## Live App

**http://63.35.198.223/**

![ECS Lamp Stack App](sreenshots/lamp-stack-app-ecs-home-page.png)

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

## Summary

- Your PHP app reads DB credentials from `getenv()`
- ECS injects secrets securely as environment variables
- No `.env` file is needed in the container

---

## 👨‍💻 Author

**Humaidu Ali Mohammed**  
Containerized LAMP App • ECS Fargate Deployment Lab with AWS Secrets Manager

---

## 📜 License

MIT License