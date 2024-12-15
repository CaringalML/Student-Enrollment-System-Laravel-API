# Infrastructure Variables
variable "aws_region" {
  description = "AWS region where the resources will be created"
  type        = string
}

# ECR Repository Variables
variable "repository_name" {
  description = "Name of the ECR repository"
  type        = string
}

variable "image_tag_mutability" {
  description = "The tag mutability setting for the repository"
  type        = string
  default     = "MUTABLE"
}

variable "scan_on_push" {
  description = "Indicates whether images are scanned after being pushed to the repository"
  type        = bool
  default     = true
}

variable "encryption_type" {
  description = "The encryption type to use for the repository"
  type        = string
  default     = "AES256"
}

variable "max_image_count" {
  description = "Maximum number of images to keep in the repository"
  type        = number
  default     = 30
}

# General Configuration
variable "environment" {
  description = "Environment (e.g., production, staging, development)"
  type        = string
}

variable "project_name" {
  description = "Name of the project"
  type        = string
}

variable "default_tags" {
  description = "Default tags to apply to all resources"
  type        = map(string)
}

# Container Configuration
variable "container_cpu" {
  description = "CPU units for the container (1024 = 1 vCPU)"
  type        = number
  default     = 1024
}

variable "container_memory" {
  description = "Memory for the container in MiB"
  type        = number
  default     = 2048
}

variable "container_port" {
  description = "Port exposed by the container"
  type        = number
  default     = 80
}

# ECS Cluster Configuration
variable "cluster_name" {
  description = "Name of the ECS cluster"
  type        = string
  default     = "laravel-node"
}

variable "enable_container_insights" {
  description = "Enable CloudWatch Container Insights for the cluster"
  type        = bool
  default     = true
}

# Laravel Application Configuration
variable "app_name" {
  type        = string
  description = "Laravel application name"
  default     = "Laravel"
}

variable "app_key" {
  type        = string
  description = "Laravel application key"
  sensitive   = true
}

variable "app_url" {
  type        = string
  description = "Laravel application URL"
}

variable "app_debug" {
  type        = string
  description = "Laravel debug mode"
  default     = "false"
}

# Logging Configuration
variable "log_channel" {
  type        = string
  description = "Laravel log channel"
  default     = "stack"
}

variable "log_deprecations_channel" {
  type        = string
  description = "Laravel deprecations log channel"
  default     = "null"
}

variable "log_level" {
  type        = string
  description = "Laravel log level"
  default     = "debug"
}

# Database Configuration
variable "db_connection" {
  description = "Database connection type"
  type        = string
  default     = "mysql"
}

variable "db_port" {
  description = "Database port"
  type        = number
  default     = 3306
}

variable "db_name" {
  description = "Database name"
  type        = string
}

variable "db_username" {
  description = "Database username"
  type        = string
}

variable "db_password" {
  description = "Database password"
  type        = string
  sensitive   = true
}

# Cache and Session Configuration
variable "broadcast_driver" {
  type        = string
  description = "Laravel broadcast driver"
  default     = "log"
}

variable "cache_driver" {
  type        = string
  description = "Laravel cache driver"
  default     = "file"
}

variable "filesystem_disk" {
  type        = string
  description = "Laravel filesystem disk"
  default     = "s3"
}

variable "queue_connection" {
  type        = string
  description = "Laravel queue connection"
  default     = "sync"
}

variable "session_driver" {
  type        = string
  description = "Laravel session driver"
  default     = "file"
}

variable "session_lifetime" {
  type        = string
  description = "Laravel session lifetime"
  default     = "120"
}

# Redis Configuration
variable "redis_host" {
  type        = string
  description = "Redis host"
  default     = "127.0.0.1"
}

variable "redis_password" {
  type        = string
  description = "Redis password"
  sensitive   = true
  default     = "null"
}

variable "redis_port" {
  type        = string
  description = "Redis port"
  default     = "6379"
}

# Memcached Configuration
variable "memcached_host" {
  type        = string
  description = "Memcached host"
  default     = "127.0.0.1"
}

# Mail Configuration
variable "mail_mailer" {
  type        = string
  description = "Laravel mail mailer"
  default     = "smtp"
}

variable "mail_host" {
  type        = string
  description = "Laravel mail host"
  default     = "mailpit"
}

variable "mail_port" {
  type        = string
  description = "Laravel mail port"
  default     = "1025"
}

variable "mail_username" {
  type        = string
  description = "Laravel mail username"
  sensitive   = true
  default     = "null"
}

variable "mail_password" {
  type        = string
  description = "Laravel mail password"
  sensitive   = true
  default     = "null"
}

variable "mail_encryption" {
  type        = string
  description = "Laravel mail encryption"
  default     = "null"
}

variable "mail_from_address" {
  type        = string
  description = "Laravel mail from address"
  default     = "hello@example.com"
}

variable "mail_from_name" {
  type        = string
  description = "Laravel mail from name"
  default     = "Laravel"
}

# AWS Configuration
variable "aws_access_key_id" {
  type        = string
  description = "AWS access key ID"
  sensitive   = true
}

variable "aws_secret_access_key" {
  type        = string
  description = "AWS secret access key"
  sensitive   = true
}

variable "aws_bucket" {
  type        = string
  description = "AWS S3 bucket name"
}

variable "aws_use_path_style_endpoint" {
  type        = string
  description = "AWS S3 path style endpoint setting"
  default     = "false"
}

# CloudFront Configuration
variable "cloudfront_domain" {
  type        = string
  description = "CloudFront domain"
}

# Pusher Configuration
variable "pusher_app_id" {
  type        = string
  description = "Pusher app ID"
  default     = ""
}

variable "pusher_app_key" {
  type        = string
  description = "Pusher app key"
  sensitive   = true
  default     = ""
}

variable "pusher_app_secret" {
  type        = string
  description = "Pusher app secret"
  sensitive   = true
  default     = ""
}

variable "pusher_host" {
  type        = string
  description = "Pusher host"
  default     = ""
}

variable "pusher_port" {
  type        = string
  description = "Pusher port"
  default     = "443"
}

variable "pusher_scheme" {
  type        = string
  description = "Pusher scheme"
  default     = "https"
}

variable "pusher_app_cluster" {
  type        = string
  description = "Pusher app cluster"
  default     = "mt1"
}

# Vite Configuration
variable "vite_pusher_app_key" {
  type        = string
  description = "Vite Pusher app key"
  default     = ""
}

variable "vite_pusher_host" {
  type        = string
  description = "Vite Pusher host"
  default     = ""
}

variable "vite_pusher_port" {
  type        = string
  description = "Vite Pusher port"
  default     = "443"
}

variable "vite_pusher_scheme" {
  type        = string
  description = "Vite Pusher scheme"
  default     = "https"
}

variable "vite_pusher_app_cluster" {
  type        = string
  description = "Vite Pusher app cluster"
  default     = "mt1"
}