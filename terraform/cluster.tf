# ECS Cluster
resource "aws_ecs_cluster" "laravel_cluster" {
  name = "laravel-node"

  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  tags = var.default_tags
}

# Enable Fargate Capacity Providers
resource "aws_ecs_cluster_capacity_providers" "cluster_capacity" {
  cluster_name = aws_ecs_cluster.laravel_cluster.name

  capacity_providers = ["FARGATE", "FARGATE_SPOT"]

  default_capacity_provider_strategy {
    capacity_provider = "FARGATE"
    base             = 1
    weight           = 1
  }
}

# CloudWatch Log Group for Cluster
resource "aws_cloudwatch_log_group" "cluster_logs" {
  name              = "/ecs/laravel-node"
  retention_in_days = 30
  tags             = var.default_tags
}

