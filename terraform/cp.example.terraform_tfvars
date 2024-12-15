# AWS Region
aws_region = "ap-southeast-2"  # Sydney region

# ECR Repository Configuration
repository_name = "student-enrollment-laravel-api"
environment     = "production"
project_name    = "student-enrollment"

# Image Configuration
image_tag_mutability = "MUTABLE"
scan_on_push        = true
encryption_type     = "AES256"

# Lifecycle Policy Configuration
max_image_count     = 30

# Tags
default_tags = {
  Name        = "student-enrollment-laravel-api"
  Environment = "production"
  Project     = "student-enrollment"
  Managed_by  = "terraform"
}

# Container Configuration
container_cpu    = 1024  # 1 vCPU
container_memory = 2048  # 2GB RAM
container_port   = 80

# Cluster Configuration
cluster_name             = "laravel-node"
enable_container_insights = true

# Database Configuration
db_connection = "mysql"
db_port      = 3306
db_name      = "collage"
db_username  = "admin"
db_password  = " "

# Laravel Environment Variables
app_name     = "Laravel"
app_key      = " "  # put you key here, run php artisan key:generate
app_url      = "http://127.0.0.1:8000"
app_debug    = "false"

# Logging Configuration
log_channel              = "stack"
log_deprecations_channel = "null"
log_level               = "debug"

# Cache and Session Configuration
broadcast_driver  = "log"
cache_driver     = "file"
filesystem_disk  = "s3"
queue_connection = "sync"
session_driver   = "file"
session_lifetime = "120"

# Redis Configuration
redis_host     = "127.0.0.1"
redis_password = "null"
redis_port     = "6379"

# Memcached Configuration
memcached_host = "127.0.0.1"

# Mail Configuration
mail_mailer      = "smtp"
mail_host        = "mailpit"
mail_port        = "1025"
mail_username    = "null"
mail_password    = "null"
mail_encryption  = "null"
mail_from_address = "hello@example.com"
mail_from_name   = "Laravel"

# AWS S3 Configuration
aws_access_key_id     = " " # put here your AWS account access key from IAM
aws_secret_access_key = " " # put here your AWS account secret form IAM
aws_bucket            = "caringaldevops"
aws_use_path_style_endpoint = "false"

# CloudFront Configuration
cloudfront_domain = "enrollment.martincaringal.co.nz"

# Pusher Configuration
pusher_app_id      = ""
pusher_app_key     = ""
pusher_app_secret  = ""
pusher_host        = ""
pusher_port        = "443"
pusher_scheme      = "https"
pusher_app_cluster = "mt1"

# Vite Configuration
vite_pusher_app_key     = ""
vite_pusher_host        = ""
vite_pusher_port        = "443"
vite_pusher_scheme      = "https"
vite_pusher_app_cluster = "mt1"