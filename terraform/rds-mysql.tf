# DB Subnet Group
resource "aws_db_subnet_group" "mysql" {
  name        = "student-enrollment-mysql-subnet-group"
  description = "DB subnet group for student enrollment MySQL"
  subnet_ids  = [aws_subnet.private_1.id, aws_subnet.private_2.id]
  
  tags = var.default_tags
}

# RDS Instance
resource "aws_db_instance" "caringal" {
  identifier           = "student-enrollment-mysql"
  engine              = "mysql"
  engine_version      = "8.0.35"
  instance_class      = "db.t3.micro"
  allocated_storage   = 20
  storage_type        = "gp2"
  
  # Database credentials from variables
  db_name             = var.db_name
  username            = var.db_username
  password            = var.db_password
  port                = var.db_port
  
  # Network settings
  db_subnet_group_name   = aws_db_subnet_group.mysql.name
  vpc_security_group_ids = [aws_security_group.database.id]
  publicly_accessible    = false
  
  # Backup and maintenance
  backup_retention_period = 7
  backup_window          = "03:00-04:00"
  maintenance_window     = "Mon:04:00-Mon:05:00"
  
  # Free tier settings
  multi_az             = false
  
  # Final snapshot configuration
  skip_final_snapshot  = false
 final_snapshot_identifier = "student-enrollment-mysql-final-snapshot-${formatdate("YYYYMMDDHHmmss", timestamp())}"
  
  # Performance Insights (disabled for free tier)
  performance_insights_enabled = false
  
  # Enhanced monitoring (disabled for free tier)
  monitoring_interval = 0
  
  # Auto minor version upgrade
  auto_minor_version_upgrade = true
  
  tags = var.default_tags
}