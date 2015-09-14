CREATE TABLE plg_AddProduct_tokens(
    token_id text NOT NULL,
    onetime_key text NOT NULL,
    member_id int NOT NULL,
    appli_id text NOT NULL,
    allow_flg smallint DEFAULT 0,
    del_flg smallint DEFAULT 0,
    last_access_date timestamp NOT NULL DEFAULT 0,
    create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_date timestamp NOT NULL,
    PRIMARY KEY (token_id(40)),
    UNIQUE (onetime_key(40))
);

INSERT INTO `mtb_auth_excludes`
    (`id`,`name`,`rank`)
SELECT
    MAX(id)+1,
    'addproduct/plg_AddProduct_login.php',
    MAX(rank)+1
FROM mtb_auth_excludes;
