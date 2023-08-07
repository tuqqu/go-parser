package test

import "time"

type Vec1[E any] []E
type Vec2[E any] []E
type Vec3[E comparable] []E

type a struct {
	a       string
	b       int             `tag`
	c       struct{ x int } "tag"
	d       []int
	c       *int
	d, e, f []*string
	g, h, i *map[int][2]string `tag`
	int
	string `tag`
	*uint
	*uint32 `tag`
	Vec1[int]
	Vec2[*uint32] `tag`
	*Vec3[string]
	time.Time "tag"
	*time.Duration
}
