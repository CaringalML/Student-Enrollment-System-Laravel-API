# ECS Service
resource "aws_ecs_service" "main" {
  name                   = "student-enrollment-service"
  cluster               = aws_ecs_cluster.laravel_cluster.id
  task_definition       = aws_ecs_task_definition.laravel_app.arn
  desired_count         = 2
  platform_version      = "LATEST"
  force_new_deployment  = true

  network_configuration {
    subnets          = [aws_subnet.private_1.id, aws_subnet.private_2.id]
    security_groups  = [aws_security_group.fargate.id]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.app.arn
    container_name   = "student-enrollment-laravel-api"
    container_port   = var.container_port
  }

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  deployment_controller {
    type = "ECS"
  }

  # Updated capacity provider strategy for higher scale
  capacity_provider_strategy {
    capacity_provider = "FARGATE"
    base             = 2    # Minimum 2 tasks on regular Fargate
    weight           = 1
  }

  capacity_provider_strategy {
    capacity_provider = "FARGATE_SPOT"
    base             = 0
    weight           = 3    # More weight on SPOT for cost-effective scaling
  }

  lifecycle {
    ignore_changes = [desired_count]
  }

  tags = var.default_tags
}

# CloudWatch Auto Scaling Target
resource "aws_appautoscaling_target" "ecs_target" {
  max_capacity       = 20    # Handle high load
  min_capacity       = 2     # Minimum 2 tasks
  resource_id        = "service/${aws_ecs_cluster.laravel_cluster.name}/${aws_ecs_service.main.name}"
  scalable_dimension = "ecs:service:DesiredCount"
  service_namespace  = "ecs"
}

# CPU Auto Scaling Policy
resource "aws_appautoscaling_policy" "cpu" {
  name               = "cpu-auto-scaling"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.ecs_target.resource_id
  scalable_dimension = aws_appautoscaling_target.ecs_target.scalable_dimension
  service_namespace  = aws_appautoscaling_target.ecs_target.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageCPUUtilization"
    }
    target_value = 70
    scale_in_cooldown  = 300  # 5 minutes
    scale_out_cooldown = 60   # 1 minute
  }
}

# Memory Auto Scaling Policy
resource "aws_appautoscaling_policy" "memory" {
  name               = "memory-auto-scaling"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.ecs_target.resource_id
  scalable_dimension = aws_appautoscaling_target.ecs_target.scalable_dimension
  service_namespace  = aws_appautoscaling_target.ecs_target.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageMemoryUtilization"
    }
    target_value = 80
    scale_in_cooldown  = 300  # 5 minutes
    scale_out_cooldown = 60   # 1 minute
  }
}

# Request Count Auto Scaling Policy
resource "aws_appautoscaling_policy" "request_count" {
  name               = "request-count-scaling"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.ecs_target.resource_id
  scalable_dimension = aws_appautoscaling_target.ecs_target.scalable_dimension
  service_namespace  = aws_appautoscaling_target.ecs_target.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ALBRequestCountPerTarget"
      resource_label        = "${aws_lb.main.arn_suffix}/${aws_lb_target_group.app.arn_suffix}"
    }
    target_value = 1000  # Scale when requests per target exceed 1000
    scale_in_cooldown  = 300  # 5 minutes
    scale_out_cooldown = 60   # 1 minute
  }
}