SELECT TOP 1
    u.documentNumber                                     AS identification_number
    , utd.name                                           AS document_type
    , u.idUser                                           AS id_patient_gomedisys
    , CONCAT_WS(' ',
        u.firstGivenName,
        u.secondGiveName,
        u.firstFamilyName,
        u.secondFamilyName)                              AS fullname
    , uca.name                                           AS sex
    , up.homeAddress                                     AS home_address
    , up.telecom                                         AS cellphone
    , up.phoneHome                                        AS homephone
    , up.email                                            AS email
    , FORMAT(up.birthDate, 'yyyy-MM-dd')                  AS birthdate
    , CASE up.idBloodType
        WHEN 46 THEN 'A+' WHEN 84 THEN 'A+'
        WHEN 88 THEN 'A-'
        WHEN 48 THEN 'AB+' WHEN 86 THEN 'AB+'
        WHEN 51 THEN 'AB-'
        WHEN 47 THEN 'B+' WHEN 85 THEN 'B+'
        WHEN 50 THEN 'B-'
        WHEN 45 THEN 'O+' WHEN 83 THEN 'O+'
        WHEN 49 THEN 'O-' WHEN 87 THEN 'O-'
        ELSE 'N/A'
      END                                                 AS blood_type
    , e.identifier                                        AS admission_number
    , FORMAT(e.dateStart, 'yyyy-MM-dd HH:mm')              AS admission_date
FROM users u
    INNER JOIN userConfTypeDocuments utd ON u.idDocumentType = utd.idTypeDocument
    INNER JOIN userPeople up ON up.idUser = u.idUser
    INNER JOIN userConfAdministrativeSex uca ON up.idAdministrativeSex = uca.idAdministrativeSex
    LEFT JOIN encounters e
        ON e.idUserPatient = u.idUser
        AND e.idUserCompany = 108240
        AND e.idStatus IN (1,2,3)
WHERE u.isActive = 1
    AND CAST(u.documentNumber AS VARCHAR(20)) = CAST(? AS VARCHAR(20))
ORDER BY e.idEncounter DESC;