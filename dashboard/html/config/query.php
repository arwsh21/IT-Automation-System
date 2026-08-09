<?php
// session_start();
require_once 'database.php';

class StudentQueries {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        echo "connected";
    }
    
    // Insert Student
    public function addStudent($data) {
        try {
            $query = "INSERT INTO STUDENT (name, email, password, batch, department) 
                      VALUES (:name, :email, :password, :batch, :department)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $data['password']);
            $stmt->bindParam(':batch', $data['batch']);
            $stmt->bindParam(':department', $data['department']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Student added successfully', 'id' => $this->conn->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Failed to add student'];
            }
        } catch(PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                return ['success' => false, 'message' => 'Email already exists!'];
            } else {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }
        }
    }
    
    // Get All Students with Search
    public function getAllStudents($search = '', $limit = 10, $offset = 0) {
        try {
            $query = "SELECT * FROM STUDENT";
            if ($search) {
                $query .= " WHERE name LIKE :search OR email LIKE :search OR department LIKE :search OR batch LIKE :search";
            }
            $query .= " ORDER BY student_id DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            if ($search) {
                $searchParam = "%$search%";
                $stmt->bindParam(':search', $searchParam);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Get Single Student by ID
    public function getStudentById($id) {
        try {
            $query = "SELECT * FROM STUDENT WHERE student_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return null;
        }
    }
    
    // Update Student
    public function updateStudent($id, $data) {
        try {
            $query = "UPDATE STUDENT SET 
                      name = :name, 
                      email = :email, 
                      batch = :batch, 
                      department = :department";
            
            if (!empty($data['password'])) {
                $query .= ", password = :password";
            }
            
            $query .= " WHERE student_id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':batch', $data['batch']);
            $stmt->bindParam(':department', $data['department']);
            $stmt->bindParam(':id', $id);
            
            if (!empty($data['password'])) {
                $stmt->bindParam(':password', $data['password']);
            }
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Student updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update student'];
            }
        } catch(PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                return ['success' => false, 'message' => 'Email already exists!'];
            } else {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }
        }
    }
    
    // Delete Student
    public function deleteStudent($id) {
        try {
            // Check if student has any references
            $checkQuery = "SELECT COUNT(*) as count FROM PC WHERE assigned_to = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete student - they have assigned PCs'];
            }
            
            $query = "DELETE FROM STUDENT WHERE student_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Student deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete student'];
            }
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Cannot delete student - they may have tickets or references'];
        }
    }
    
    // Get Total Students Count
    public function getTotalStudents($search = '') {
        try {
            $query = "SELECT COUNT(*) as total FROM STUDENT";
            if ($search) {
                $query .= " WHERE name LIKE :search OR email LIKE :search OR department LIKE :search";
            }
            $stmt = $this->conn->prepare($query);
            if ($search) {
                $searchParam = "%$search%";
                $stmt->bindParam(':search', $searchParam);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch(PDOException $e) {
            return 0;
        }
    }
    
    // Get Students by Department
    public function getStudentsByDepartment($department) {
        try {
            $query = "SELECT * FROM STUDENT WHERE department = :department ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':department', $department);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Get Student Statistics
    public function getStudentStatistics() {
        try {
            $stats = [];
            
            // Total students
            $query = "SELECT COUNT(*) as total FROM STUDENT";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Students by department
            $query = "SELECT department, COUNT(*) as count FROM STUDENT GROUP BY department";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Students by batch
            $query = "SELECT batch, COUNT(*) as count FROM STUDENT GROUP BY batch ORDER BY batch DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['by_batch'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch(PDOException $e) {
            return ['total' => 0, 'by_department' => [], 'by_batch' => []];
        }
    }
}
?>