create table cinema
(
    id       int auto_increment
        primary key,
    name     varchar(255) not null,
    location varchar(255) not null
)
    collate = utf8mb4_unicode_ci;

create table doctrine_migration_versions
(
    version        varchar(191) not null
        primary key,
    executed_at    datetime     null,
    execution_time int          null
);

create table film
(
    id            int auto_increment
        primary key,
    title         varchar(180) not null,
    film_filename varchar(180) not null,
    description   varchar(180) not null,
    age_min       int          not null,
    duration      int          not null,
    is_favorite   tinyint(1)   not null,
    created_at    datetime     null
)
    collate = utf8mb4_unicode_ci;

create table film_cinema
(
    film_id   int not null,
    cinema_id int not null,
    primary key (film_id, cinema_id),
    constraint FK_BF7139BE567F5183
        foreign key (film_id) references film (id)
            on delete cascade,
    constraint FK_BF7139BEB4CB84B6
        foreign key (cinema_id) references cinema (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create index IDX_BF7139BE567F5183
    on film_cinema (film_id);

create index IDX_BF7139BEB4CB84B6
    on film_cinema (cinema_id);

create table genre
(
    id   int auto_increment
        primary key,
    name varchar(255) not null
)
    collate = utf8mb4_unicode_ci;

create table film_genre
(
    film_id  int not null,
    genre_id int not null,
    primary key (film_id, genre_id),
    constraint FK_1A3CCDA84296D31F
        foreign key (genre_id) references genre (id)
            on delete cascade,
    constraint FK_1A3CCDA8567F5183
        foreign key (film_id) references film (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create index IDX_1A3CCDA84296D31F
    on film_genre (genre_id);

create index IDX_1A3CCDA8567F5183
    on film_genre (film_id);

create table room
(
    id               int auto_increment
        primary key,
    number           int          not null,
    quality          varchar(180) not null,
    rows_room        int          not null,
    columns_room     int          not null,
    total_seats      int          not null,
    accessible_seats json         not null,
    stairs           json         not null
)
    collate = utf8mb4_unicode_ci;

create table reparation
(
    id              int auto_increment
        primary key,
    description     varchar(255) not null,
    statut          varchar(50)  not null,
    date_creation   datetime     not null,
    date_reparation datetime     null,
    room_id         int          not null,
    constraint FK_8FDF219D54177093
        foreign key (room_id) references room (id)
)
    collate = utf8mb4_unicode_ci;

create index IDX_8FDF219D54177093
    on reparation (room_id);

create table session
(
    id             int auto_increment
        primary key,
    reserved_seats json           null,
    start_date     datetime       not null,
    end_date       datetime       not null,
    price          decimal(10, 2) not null,
    film_id        int            not null,
    cinema_id      int            not null,
    room_id        int            not null,
    constraint FK_D044D5D454177093
        foreign key (room_id) references room (id),
    constraint FK_D044D5D4567F5183
        foreign key (film_id) references film (id),
    constraint FK_D044D5D4B4CB84B6
        foreign key (cinema_id) references cinema (id)
)
    collate = utf8mb4_unicode_ci;

create index IDX_D044D5D454177093
    on session (room_id);

create index IDX_D044D5D4567F5183
    on session (film_id);

create index IDX_D044D5D4B4CB84B6
    on session (cinema_id);

create table user
(
    id                    int auto_increment
        primary key,
    confirmation_token    varchar(64)  null,
    is_active             tinyint(1)   not null,
    is_temporary_password tinyint(1)   null,
    email                 varchar(180) not null,
    firstname             varchar(255) not null,
    lastname              varchar(255) not null,
    roles                 json         not null,
    password              varchar(255) not null,
    constraint UNIQ_8D93D649E7927C74
        unique (email)
)
    collate = utf8mb4_unicode_ci;

create table reservation
(
    id          int auto_increment
        primary key,
    seats       json           not null,
    total_price decimal(10, 2) not null,
    session_id  int            not null,
    user_id     int            not null,
    qr_code_url varchar(255)   null,
    created_at  datetime       not null,
    constraint FK_42C84955613FECDF
        foreign key (session_id) references session (id),
    constraint FK_42C84955A76ED395
        foreign key (user_id) references user (id)
)
    collate = utf8mb4_unicode_ci;

create index IDX_42C84955613FECDF
    on reservation (session_id);

create index IDX_42C84955A76ED395
    on reservation (user_id);

create table review
(
    id             int auto_increment
        primary key,
    rating         int        not null,
    description    longtext   null,
    validated      tinyint(1) not null,
    user_id        int        not null,
    film_id        int        not null,
    reservation_id int        not null,
    constraint FK_794381C6567F5183
        foreign key (film_id) references film (id),
    constraint FK_794381C6A76ED395
        foreign key (user_id) references user (id),
    constraint FK_794381C6B83297E7
        foreign key (reservation_id) references reservation (id)
)
    collate = utf8mb4_unicode_ci;

create index IDX_794381C6567F5183
    on review (film_id);

create index IDX_794381C6A76ED395
    on review (user_id);

create index IDX_794381C6B83297E7
    on review (reservation_id);

