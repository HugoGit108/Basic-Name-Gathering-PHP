<?php

namespace bng\Models;

use bng\Models\BaseModel;

class AdminModel extends BaseModel
{
    #=================================================
    public function get_all_clients()
    {
        $this->db_connect();
        $data = $this->query(
            "SELECT p.id,
            AES_DECRYPT(p.name, '" . MYSQL_AES_KEY . "') name,
            p.gender,
            p.birthdate,
            AES_DECRYPT(p.email, '" . MYSQL_AES_KEY . "') email,
            AES_DECRYPT(p.phone, '" . MYSQL_AES_KEY . "') phone,
            p.interests,
            p.created_at,
            AES_DECRYPT(a.name, '" . MYSQL_AES_KEY . "' ) agent
            FROM persons p 
            LEFT JOIN agents a 
            ON p.id_agent = a.id
            WHERE p.deleted_at IS NULL 
            ORDER BY created_at DESC            
            "
        );

        return $data;
    }

    #=================================================
    public function get_all_clients_stats()
    {

        $sql = "
        SELECT * FROM 
        (
            SELECT p.id_agent,
            AES_DECRYPT(a.name ,'" . MYSQL_AES_KEY . "') agente,
            COUNT(*) total_clientes
            FROM persons p 
            LEFT JOIN agents a 
            ON a.id = p.id_agent
            WHERE p.deleted_at IS NULL
            GROUP BY id_agent) a 
            ORDER BY total_clientes DESC
        ";

        $this->db_connect();
        $results = $this->query($sql);
        return $results->results;
    }

    #=================================================
    public function get_global_stats()
    {
        // get global stats from db
        $this->db_connect();

        // get total of agents that are not admins
        $results['totalAgents'] = $this->query("SELECT COUNT(*) value FROM agents WHERE PROFILE <> 'admin'")->results[0];

        // get total of clients
        $results['totalClients'] = $this->query("SELECT COUNT(*) value FROM persons")->results[0];

        // total inactive clients
        $results['totalInactiveClients'] = $this->query("SELECT COUNT(*) value FROM persons WHERE deleted_at IS NOT NULL")->results[0];

        // average number of clients per agent
        $results['averageClientsPerAgent'] = $this->query(
            "
            SELECT (total_persons / total_agents) AS value
            FROM (
                SELECT
                    (SELECT COUNT(*) FROM persons) AS total_persons,
                    (SELECT COUNT(*) FROM agents WHERE profile <> 'admin') AS total_agents
            ) a
            "
        )->results[0];

        // get younger client
        $tmp = $this->query("
            SELECT TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) value
            FROM persons ORDER BY birthdate DESC LIMIT 1  
        ");

        if ($tmp->affected_rows == 0) {
            $results['youngerClient'] = null;
        } else {
            $results['youngerClient'] = $tmp->results[0];
        }

        // get older client
        $tmp = $this->query("
            SELECT TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) value
            FROM persons ORDER BY birthdate ASC LIMIT 1  
        ");

        if($tmp->affected_rows == 0) {
            $results['oldestClient'] = null;
        } else {
            $results['oldestClient'] = $tmp->results[0];
        }

        // get the average age of the clients
        $results['averageAge'] = $this->query("SELECT AVG(TIMESTAMPDIFF(YEAR,birthdate,CURDATE())) value FROM persons")->results[0];

        // gender percentage -> female
        $results['percentageFemales'] = $this->query("
            SELECT 
            CAST((total_females/total_clients) * 100 AS DECIMAL(5,2)) value
            FROM (
                SELECT 
                (SELECT COUNT(*) FROM persons) total_clients,
                (SELECT COUNT(*) FROM persons WHERE gender = 'f') total_females
            ) a 
        ")->results[0];

        // gender percentage -> male
        $results['percentageMales'] = $this->query("
            SELECT 
            CAST((total_males/total_clients) * 100 AS DECIMAL(5,2)) value
            FROM (
                SELECT 
                (SELECT COUNT(*) FROM persons) total_clients,
                (SELECT COUNT(*) FROM persons WHERE gender = 'm') total_males
            ) a 
        ")->results[0];

        return $results;
    }

    #=================================================
    public function get_agents_for_management()
    {
        // gets agents data

        $this->db_connect();
        $results = $this->query("
        SELECT
            id,
            AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name,
            passwrd,
            profile,
            last_login,
            created_at,
            updated_at,
            deleted_at
        FROM agents
        ");

        return $results;
    }

    #=================================================
    public function check_if_user_exists($agent)
    {
        $params = [
            ':name' => $agent
        ];

        $this->db_connect();
        $results = $this->query(
            "
        SELECT id FROM agents 
        WHERE AES_ENCRYPT(:name , '" . MYSQL_AES_KEY . "') = name
        ",
            $params
        );

        if ($results->affected_rows == 0) {
            return false;
        } else {
            return true;
        }
    }

    #=================================================
    public function add_new_agent ($postData)
    {
        // add new agent do the database

        // generate purl (personal URL)
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $purl = substr(str_shuffle($chars),0,20);

        $params = [
            ':name' => $postData['text_name'],
            ':profile' => $postData['select_profile'],
            ':purl' => $purl
        ];

        $this->db_connect();
        $results = $this->non_query("
            INSERT INTO agents VALUES (
            0,
            AES_ENCRYPT(:name, '" . MYSQL_AES_KEY . "'),
            NULL,
            :profile,
            :purl,
            NULL,
            NULL,
            NOW(),
            NULL,
            NULL 
            )
        ",$params);

        if ($results->affected_rows == 0) {
            return ['status' => 'error'];
        } else {
            return [
                'status' => 'success',
                'email' => $postData['text_name'],
                'purl' => $purl
            ];
        }
    }

    #=================================================
    public function get_agent_data($agentId)
    {
        $params = [
            ':id' => $agentId
        ];
        $this->db_connect();
        $results = $this->query("
        SELECT
        id,
        AES_DECRYPT(name, '" .MYSQL_AES_KEY ."') name,
        profile,
        created_at,
        updated_at,
        deleted_at
        FROM agents
        WHERE id = :id
        ",$params);

        return $results;
    }

    #=================================================
    public function check_if_user_exists_with_same_name($id,$agentName)
    {
        // check for a agent with the same name
        $params = [
            ':id' => $id,
            ':name' => $agentName
        ];

        $this->db_connect();
        $results = $this->query("
            SELECT id FROM agents 
            WHERE AES_ENCRYPT(:name, '". MYSQL_AES_KEY ."') = name
            AND id <> :id
        ",$params);

        return $results->affected_rows != 0 ? true : false;
    }

    #=================================================
    public function edit_agent($id,$postData)
    {
        // edits the agent data
        $params = [
            ':id' => $id,
            'name' => $postData['text_name'],
            'profile' => $postData['select_profile']
        ];
        $this->db_connect();
        $results = $this->non_query("
        UPDATE agents SET 
        name = AES_ENCRYPT(:name , '". MYSQL_AES_KEY ."'),
        profile = :profile,
        updated_at = NOW()
        WHERE id = :id 
        ",$params);
        return $results;
    }

    #=================================================
    public function get_agent_data_and_clients($agentId)
    {
        // will return the agent personal data and clients
        $params = [
            ':id' => $agentId
        ];
        $this->db_connect();
        $results = $this->query("
        SELECT 
        id,
        AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name,
        profile,
        created_at,
        updated_at,
        deleted_at,
        (SELECT COUNT(*) FROM persons WHERE id_agent = :id) totalClients
        FROM agents 
        WHERE id = :id
        ",$params);

        return $results;
    }

    public function deleted_agent($agentId)
    {
        // soft deleted on agent
        $params = [
            ':id' => $agentId
        ];
        $this->db_connect();
        $results = $this->non_query("
        UPDATE agents SET 
        deleted_at = NOW()
        WHERE id = :id 
        ",$params);

        return $results;
    }

    #=================================================
    public function recover_agent($agentId)
    {
        // recovers the agent
        $params = [
            'id' => $agentId
        ];
        $this->db_connect();
        $results = $this->non_query("
        UPDATE agents SET 
        deleted_at = NULL 
        WHERE id = :id
        ",$params);

        return $results;
    }

    public function get_agent_data_and_total_clients() 
    {
        // will return all the data from the client(agents)
        $this->db_connect();
        $results = $this->query("
        SELECT
        AES_DECRYPT(name, '". MYSQL_AES_KEY ."') name,
        profile,
        CASE 
            WHEN passwrd IS NOT NULL THEN 'active'
            WHEN passwrd IS NULL THEN 'not active'
        END active,
        last_login,
        created_at,
        updated_at,
        deleted_at,
        a.total_active_clients,
        b.total_deleted_clients
        FROM agents
        LEFT JOIN (
            SELECT id_agent,COUNT(*) total_deleted_clients 
            FROM persons
            WHERE deleted_at IS NOT NULL
            GROUP BY id_agent) b
            ON id  = b.id_agent
        LEFT JOIN (
            SELECT id_agent,COUNT(*) total_active_clients
            FROM persons 
            WHERE deleted_at IS NULL 
            GROUP BY id_agent) a
            ON id  = a.id_agent
        ");

        return $results;
    }
}
