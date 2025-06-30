# 🚀 Containerized LAMP Application on Amazon ECS Fargate

This project demonstrates how to build and deploy a containerized LAMP (Linux, Apache, MySQL, PHP) application on **Amazon ECS using Fargate**. The application logs IP visits and stores them in an **Amazon RDS MySQL database**.

---

## 📦 Stack

- **PHP 8.x** with Apache
- **MySQL** on Amazon RDS
- **Docker** (built using Colima with x86_64 support)
- **Amazon ECR** – for storing Docker images
- **Amazon ECS (Fargate)** – for container orchestration
- **AWS CLI & Console** – for infrastructure interaction

---

## 📁 Project Structure

```
lamp-app/
├── Dockerfile
├── index.php
├── config.php
├── styles.css
└── .env               # (local dev only)
```

---

## 🛠 Setup Instructions

### 1. 🔧 Build Docker Image (on Mac with Colima)

Ensure you're using the correct architecture:

```bash
colima stop
colima delete
colima start --arch x86_64 --vm-type=qemu
docker build -t lamp-app .
```

### 2. 🐳 Push to Amazon ECR

```bash
# Authenticate
aws ecr get-login-password --region <region> |   docker login --username AWS --password-stdin <account>.dkr.ecr.<region>.amazonaws.com

# Tag and push
docker tag lamp-app:latest <account>.dkr.ecr.<region>.amazonaws.com/lamp-app:latest
docker push <account>.dkr.ecr.<region>.amazonaws.com/lamp-app:latest
```

### 3. 🖥️ Create ECS Task Definition (via AWS Console)

- Launch Type: FARGATE
- Container image: your ECR image URI
- Environment variables:
  - `DB_ENDPOINT`: `your-rds-endpoint:3306`
  - `DB_USERNAME`: `admin`
  - `DB_PASSWORD`: `yourpassword`
  - `DB_NAME`: `lampdb`

Enable logging to CloudWatch if desired.

### 4. 🚀 Deploy Service via ECS Console

- Cluster: `lamp-app-cluster`
- Launch type: FARGATE
- Network: Public subnet with `assignPublicIp = ENABLED`
- Security Group: allow inbound `TCP 80`

### 5. 🌐 Access the App

Get the public IP of the task via:

```bash
aws ecs list-tasks --cluster lamp-app-cluster
aws ecs describe-tasks --cluster lamp-app-cluster --tasks <task-id>
aws ec2 describe-network-interfaces --network-interface-ids <eni-id>   --query "NetworkInterfaces[0].Association.PublicIp" --output text
```

Visit: `http://<public-ip>`

---

## 🔐 Security Considerations

- Avoid pushing `.env` files to GitHub
- Use **AWS Secrets Manager** for production credentials
- Restrict RDS access to ECS security group only

---

## 🧹 Cleanup

```bash
aws ecs update-service --cluster lamp-app-cluster --service lamp-app-service --desired-count 0
aws ecs delete-service --cluster lamp-app-cluster --service lamp-app-service --force
aws ecs delete-cluster --cluster lamp-app-cluster
```

---

## 👨‍💻 Author

**Humaidu Ali Mohammed**  
Containerized LAMP App • ECS Fargate Deployment Lab

---
